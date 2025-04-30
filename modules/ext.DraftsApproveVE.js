
function changeSaveButtonTextMobile() {
    const txt = document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' )?.innerText;
    if (txt && txt !== mediaWiki.message( 'draftsapprove-view-propose' ).text()) {
        document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' ).innerText = mediaWiki.message( 'draftsapprove-view-propose' ).text();
    }
}
function changeSaveButtonText() {
    const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
    if (saveButton) {
        saveButton.innerText = mediaWiki.message( 'draftsapprove-view-propose' ).text() + '...';
    }
    new MutationObserver(changeSaveButtonTextMobile).observe(document.querySelector('#mw-teleport-target .ve-ui-overlay-global.ve-ui-overlay-global-mobile.ve-ui-overlay'), {childList: true, subtree: true});
}
mw.hook( 've.activationComplete' ).add( changeSaveButtonText );