function changeSaveButtonText() {
    // Cerca il bottone "Salva pagina..."
    var saveButton = document.querySelector('.ve-ui-toolbar-saveButton .oo-ui-labelElement-label');
    if (saveButton) {
        saveButton.textContent = 'Proponi';
    }
}
mw.hook('ve.activationComplete').add(changeSaveButtonText);