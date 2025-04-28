<?php
/**
 * Utility functions for Drafts extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;
global $egDraftsLifeSpan;
$egDraftsLifeSpan = 30;
$egDraftsCleanRatio = 0;

abstract class Drafts {
	private static bool $cleaned = false;

	/**
	 * @return int
	 */
	private static function getDraftAgeCutoff() {
		global $egDraftsLifeSpan;
		if ( !$egDraftsLifeSpan ) {
			// Drafts stay forever
			return 0;
		}
		return (int)wfTimestamp( TS_UNIX ) - ( $egDraftsLifeSpan * 60 * 60 * 24 );
	}

	/**
	 * Counts the number of existing drafts for a specific user
	 *
	 * @param Title|null $title Title of article, defaults to all articles
	 * @param int|bool|null $userID ID of user, defaults to current user
	 * @param string|null $draftStatus ID of user, defaults all statuses
	 * @return int Number of drafts which match condition parameters
	 */
	public static function num( $title = null, $userID = null, $draftStatus = null ) {

		self::clean();
		// Get database connection
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );

		// Builds where clause
		$where = [];

		// Checks if a specific title was given
		if ( $title !== null ) {
			// Get page id from title
			$pageId = $title->getArticleID();
			// Checks if page id exists
			if ( $pageId ) {
				// Adds specific page id to conditions
				$where['draft_page'] = $pageId;
			} else {
				// Adds new page information to conditions
				$where['draft_namespace'] = $title->getNamespace();
				$where['draft_title'] = $title->getDBkey();
				// page not created yet
				$where['draft_page'] = 0;
			}
		}

		if ($userID !== true) {
			// Checks if specific user was given
			if ( $userID !== null ) {
				// Adds specific user to condition
				$where['draft_user'] = $userID;
			} else {
				// Adds current user as condition
				$where['draft_user'] = RequestContext::getMain()->getUser()->getId();
			}
		}

		// Checks if specific draftStatus was given
		if ( $draftStatus !== null ) {
			// Adds specific draftStatus to condition
			$where['draft_status'] = $draftStatus;
		}

		// Get a list of matching drafts
		return $dbr->selectField( 'drafts_approve', 'COUNT(*)', $where, __METHOD__ );
	}

	/**
	 * Removes drafts which have not been modified for a period of time defined
	 * by $egDraftsCleanRatio
	 */
	public static function clean() {
		if (static::$cleaned) {return;}
		$user = RequestContext::getMain()->getUser();
		if ($user->isAnon()) {return;}
		
		global $egDraftsCleanRatio;

		// Only perform this action a fraction of the time
		if ( rand( 0, $egDraftsCleanRatio ) == 0 ) {
			// Get database connection
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			// Removes expired drafts from database
			$dbw->delete( 'drafts_approve',
				[
					'draft_savetime < ' .
						$dbw->addQuotes(
							$dbw->timestamp( self::getDraftAgeCutoff() )
						)
					. ($user->isAllowed('drafts-approve') ? '' : (' AND draft_user = ' . $user->getId()) )
				],
				__METHOD__
			);
		}
		static::$cleaned = true;
	}

	/**
	 * Re-titles drafts which point to a particlar article, as a response to the
	 * article being moved.
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 */
	public static function move( $oldTitle, $newTitle ) {
		// Get database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		// Updates title and namespace of drafts upon moving
		$dbw->update(
			'drafts_approve',
			[
				'draft_namespace' => $newTitle->getNamespace(),
				'draft_title' => $newTitle->getDBkey()
			],
			[
				'draft_page' => $newTitle->getArticleID()
			],
			__METHOD__
		);
	}

	/**
	 * Gets a list of existing drafts for a specific user
	 *
	 * @param Title|null $title Title of article, defaults to all articles
	 * @param int|bool|null $userID ID of user, defaults to current user
	 * @param string|null $draftStatus ID of user, defaults all statuses
	 * @return Draft[]|null
	 */
	public static function get( $title = null, $userID = null, $draftStatus = null ) {
		// Removes expired drafts for a more accurate list
		self::clean();

		// Gets database connection
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

		// Builds where clause
		$where = [];

		// Checks if specific title was given
		if ( $title !== null ) {
			// Get page id from title
			$pageId = $title->getArticleID();
			// Checks if page id exists
			if ( $pageId ) {
				// Adds specific page id to conditions
				$where['draft_page'] = $pageId;
			} else {
				// Adds new page information to conditions
				$where['draft_namespace'] = $title->getNamespace();
				$where['draft_title'] = $title->getDBkey();
			}
		}

		if ($userID !== true) {
			// Checks if specific user was given
			if ( $userID !== null ) {
				// Adds specific user to condition
				$where['draft_user'] = $userID;
			} else {
				// Adds current user as condition
				$where['draft_user'] = RequestContext::getMain()->getUser()->getId();
			}
		}

		// Checks if specific draftStatus was given
		if ( $draftStatus !== null ) {
			// Adds specific draftStatus to condition
			$where['draft_status'] = $draftStatus;
		}

		// Gets matching drafts from database
		$result = $dbw->select( 'drafts_approve', '*', $where, __METHOD__ );
		$drafts = [];
		if ( $result ) {
			// Creates an array of matching drafts
			foreach ( $result as $row ) {
				// Adds a new draft to the list from the row
				$drafts[] = Draft::newFromRow( $row );
			}
		}

		// Returns array of matching drafts or null if there were none
		return count( $drafts ) ? $drafts : null;
	}

	/**
	 * Outputs a table of existing drafts
	 *
	 * @param Title|null $title Title of article, defaults to all articles
	 * @param int|bool|null $userID ID of user, defaults to current user
	 * @param string|null $draftStatus ID of user, defaults all statuses
	 * @param bool $approvePage if user is in DraftsToApprove page
	 * @return string HTML to be shown to the user
	 */
	public static function display( $title = null, $userID = null, $draftStatus = null, $approvePage = false ) {
		global $wgRequest;

		// Gets draftID
		$currentDraft = Draft::newFromID( $wgRequest->getInt( 'draft', 0 ) );
		// Output HTML for list of drafts
		$drafts = self::get( $title, $userID, $draftStatus );
		if ( $drafts !== null ) {
			$html = '';
			$context = RequestContext::getMain();
			$user = $context->getUser();
			$lang = $context->getLanguage();
			$editToken = $user->getEditToken();

			// Build XML

			if ($approvePage) {
				$html .= Xml::textarea('draft-refuse-reason-field', '');
			}

			$html .= Xml::openElement( 'table',
				[
					'cellpadding' => 5,
					'cellspacing' => 0,
					'width' => '100%',
					'border' => 0,
					'id' => 'drafts-list-table'
				]
			);

			$html .= Xml::openElement( 'tr' );
			$html .= Xml::element( 'th',
				[ 'width' => '65%', 'nowrap' => 'nowrap' ],
				wfMessage( 'drafts-view-article' )->text()
			);
			$html .= Xml::element( 'th',
				null,
				wfMessage( 'drafts-view-saved' )->text()
			);
			$html .= Xml::element( 'th',
 				null,
 				wfMessage("drafts-view-status")->text()
 			);
			if ($approvePage) {
				$html .= Xml::element( 'th',
					null,
					wfMessage("drafts-author")->text()
				);
			} else {
				$html .= Xml::element( 'th',
					null,
					wfMessage("drafts-refuser")->text()
				);
				$html .= Xml::element( 'th',
					['width' => '20%', 'nowrap' => 'nowrap'],
					wfMessage("drafts-refuse-reason")->text()
				);
			}
			$html .= Xml::element( 'th' );
			$html .= Xml::closeElement( 'tr' );
			// Add existing drafts for this page and user
			/**
			 * @var $draft Draft
			 */
			$draftsTitle = SpecialPage::getTitleFor( 'Drafts' );
			$draftsToApproveTitle = SpecialPage::getTitleFor( 'DraftsToApprove' );
			foreach ( $drafts as $draft ) {
				$draftUser = '';
				$draftRefuseUser = '';
				$draftRefuseReason = '';
				if ($approvePage) {
					$draftUser = User::newFromId( $draft->getUserID() );
					$draftUser = $draftUser->getName();
				} else if ($draft->isRefused()) {
					$draftRefuseUser = User::newFromId( $draft->getRefuseUserID() );
					$draftRefuseUser = $draftRefuseUser->getName();
					$draftRefuseReason = $draft->getRefuseReason();
				}
				// Get article title text
				$htmlTitle = htmlspecialchars( $draft->getTitle()->getPrefixedText() );
				// Build Article Load link
				$urlLoad = $draft->getTitle()->getFullURL(
					'action=edit&draft=' . urlencode( (string)$draft->getID() ) . ($approvePage ? '&wpApproveView=1' : '')
				);
				// Build discard link
				$urlDiscard = $approvePage ? $draftsToApproveTitle->getFullURL(
					sprintf( 'refuse=%s&token=%s',
						urlencode( (string)$draft->getID() ),
						urlencode( $editToken )
					)
				) : $draftsTitle->getFullURL(
					sprintf( 'discard=%s&token=%s',
						urlencode( (string)$draft->getID() ),
						urlencode( $editToken )
					)
				);
				// If in edit mode, return to editor
				if (
					$wgRequest->getRawVal( 'action' ) === 'edit' ||
					$wgRequest->getRawVal( 'action' ) === 'submit'
				) {
					$urlDiscard .= '&returnto=' . urlencode( 'edit' );
				}
				// Append section to titles and links
				if ( $draft->getSection() !== null ) {
					// Detect section name
					$lines = explode( "\n", $draft->getText() );

					// If there is any content in the section
					if ( count( $lines ) > 0 ) {
						$htmlTitle .= '#' . htmlspecialchars(
							trim( trim( substr( $lines[0], 0, 255 ), '=' ) )
						);
					}
					// Modify article link and title
					$urlLoad .= '&section=' . urlencode( (string)$draft->getSection() );
					$urlDiscard .= '&section=' .
						urlencode( (string)$draft->getSection() );
				}
				// Build XML
				$html .= Xml::openElement( 'tr' );
				$html .= Xml::openElement( 'td' );
				$html .= Xml::tags( 'a',
					[
						'href' => $urlLoad,
						'class' => 'mw-draft-load-link',
						'data-draft-id' => $draft->getID(),
						'style' => 'font-weight:' .
							(
								$currentDraft->getID() == $draft->getID() ?
									'bold' : 'normal'
							)
					],
					$htmlTitle
				);
				$html .= Xml::closeElement( 'td' );
				$html .= Xml::element( 'td',
					null,
					$lang->getHumanTimestamp( MWTimestamp::getInstance( $draft->getSaveTime() ), null, $user )
				);
				$html .= Xml::element( 'td',
 					null,
 					wfMessage("drafts-view-status-" . $draft->getStatus())->text()
				);
				if ($approvePage) {
					$html .= Xml::element( 'td',
						null,
						$draftUser
					);
				} else {
					$html .= Xml::element( 'td',
						null,
						$draftRefuseUser
					);
					$html .= Xml::element( 'td',
						null,
						$draftRefuseReason
					);
				}
				$html .= Xml::openElement( 'td' );
				$html .= Xml::element( 'a',
					[
						'href' => $urlDiscard,
						'class' => 'mw-discard-draft-link'
					] + ($approvePage? [
						'onclick' => "((evt,tgt)=>{
							evt.preventDefault();
							fetch(tgt.href, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								body: new URLSearchParams({
									refuse_reason: document.querySelector('textarea[name=\"draft-refuse-reason-field\"]')?.value?.trim() || ''
								})
							}).then(()=>location.reload());
						})(event,this)"
					] : []),
					wfMessage('drafts-view-' . ($approvePage? 'refuse' : 'discard') )->text()
				);
				$html .= Xml::closeElement( 'td' );
				$html .= Xml::closeElement( 'tr' );
			}
			$html .= Xml::closeElement( 'table' );
			// Return html
			return $html;
		}
		return '';
	}
}
