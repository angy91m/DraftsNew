
function changeSaveButtonTextMobile() {
    const txt = document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' )?.innerText;
    if (txt && txt !== 'Proponi') {
        document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' ).innerText = 'Proponi';
    }
}
function changeSaveButtonText() {
    const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
    if (saveButton) {
        saveButton.innerText = 'Proponi' + '...';
    }
    new MutationObserver(changeSaveButtonTextMobile).observe(document.querySelector('#mw-teleport-target ve-ui-overlay-global .ve-ui-overlay-global-mobile.ve-ui-overlay'), {childList: true, subtree: true});
}
mw.hook( 've.activationComplete' ).add( changeSaveButtonText );