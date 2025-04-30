function changeSaveButtonText() {
    // Cerca il bottone "Salva pagina..."
    const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
    if (saveButton) {
        saveButton.textContent = 'Proponi';
    }
}
mw.hook('ve.activationComplete').add(changeSaveButtonText);