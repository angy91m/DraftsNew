<?php
/**
 * API module to load Drafts
 *
 * @file
 * @ingroup API
 * @date 26 February 2022
 * @see https://phabricator.wikimedia.org/T57451
 */
class ApiLoadDrafts extends ApiBase {

	public function execute() {
		$user = $this->getUser();

		if ( $user->isAnon() ) {
			$this->dieWithError(
				'apierror-mustbeloggedin-load-drafts',
				'notloggedin'
			);
		}

		$params = $this->extractRequestParams();

		$draft = Draft::newFromID( $params['id'] );

		// Don't let users load others' drafts, only their own
		if ( $draft->getUserID() !== $user->getId() ) {
			if (!$user->isAllowed('drafts-approve') || $draft->getStatus() !== 'proposed') {
				$this->dieWithError(
					'apierror-must-be-draft-owner',
					'notowner'
				);
			}
		}

		if ( !$draft->exists() ) {
			$this->dieWithError(
				'apierror-no-such-draft',
				'nosuchdraft'
			);
		}

		$refuseUser = null;
		if ($draft->isRefused()) {
			$refuseUser = User::newFromId($draft->getRefuseUserID());
			$refuseUser->loadFromId();
			$refuseUser = $refuseUser->getName();
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			[
				// Need more than just the draft text to mimic the behavior
				// of DraftHooks#loadForm
				'text' => $draft->getText(),
				'summary' => $draft->getSummary(),
				'scrolltop' => $draft->getScrollTop(),
				'minoredit' => $draft->getMinorEdit() ? true : false,
				'status' => $draft->getStatus(),
				'refuseReason' => $draft->getRefuseReason(),
				'refuseUser' => $refuseUser
			]
		);
	}

	public function getAllowedParams() {
		return [
			'id' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'integer',
			],
			'token' => null,
		];
	}

	public function mustBePosted() {
		return true;
	}

	public function needsToken() {
		return 'csrf';
	}

	public function getTokenSalt() {
		return '';
	}

}
