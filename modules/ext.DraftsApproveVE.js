function changeSaveButtonText() {
    const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
    if (saveButton) {
        saveButton.textContent = 'Proponi';
    }
}
function changeSaveButtonTextMobile() {
    console.log('ciao');
}

mw.hook( 've.activationComplete' ).add( changeSaveButtonText );
mw.hook( 've.saveDialog.stateChanged' ).add( changeSaveButtonTextMobile );