function changeSaveButtonText() {
    const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
    if (saveButton) {
        saveButton.textContent = 'Proponi';
    }
}
function changeSaveButtonTextMobile() {
    console.log(document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' ).innerText);
}

mw.hook( 've.activationComplete' ).add( changeSaveButtonText );
mw.hook( 've.saveDialog.stateChanged' ).add( changeSaveButtonTextMobile );