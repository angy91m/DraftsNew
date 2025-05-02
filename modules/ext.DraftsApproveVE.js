
function changeSaveButtonTextMobile() {
    const txt = document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' )?.innerText;
    if (txt && txt !== mediaWiki.message( 'draftsapprove-view-propose' ).text()) {
        document.querySelector( '.oo-ui-processDialog-navigation .oo-ui-processDialog-actions-primary .oo-ui-labelElement-label' ).innerText = mediaWiki.message( 'draftsapprove-view-propose' ).text();
    }
}
function changeSaveButtonText() {
    try {
        const saveButton = document.querySelector('.ve-ui-toolbar-group-save .oo-ui-tool-title');
        if (saveButton) {
            saveButton.innerText = mediaWiki.message( 'draftsapprove-view-propose' ).text() + '...';
        }
    } catch {}
    try {
        new MutationObserver(changeSaveButtonTextMobile).observe(document.querySelector('#mw-teleport-target .ve-ui-overlay-global.ve-ui-overlay-global-mobile.ve-ui-overlay'), {childList: true, subtree: true});
    } catch {}
}

function onActivation() {
    changeSaveButtonText();
    const target = ve.init.target;
    if (!target) return;
    const origSaveErrorHookAborted = target.saveErrorHookAborted;
    target.saveErrorHookAborted = function(data) {
        origSaveErrorHookAborted.call(this, data);
        console.log(data);
    };
}
mw.hook( 've.activationComplete' ).add( onActivation );