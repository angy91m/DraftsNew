
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
        if (data.errors && Array.isArray(data.errors) && data.errors.length) {
            const error = data.errors[0];
            if (error.data?.edit?.redirectTarget) {
                setTimeout(() => {
                    document.querySelector('.oo-ui-processDialog-errors-title').style.display = 'none';
                    document.querySelector('.oo-ui-processDialog-errors-actions').style.display = 'none';
                    document.querySelector('.oo-ui-processDialog-errors .oo-ui-flaggedElement-error').classList.replace('oo-ui-flaggedElement-error', 'oo-ui-flaggedElement-success');
                    const icon = document.querySelector('.oo-ui-processDialog-errors .oo-ui-icon-error.oo-ui-image-error');
                    icon.classList.remove('oo-ui-icon-error', 'oo-ui-image-error');
                    icon.classList.add('oo-ui-icon-success', 'oo-ui-image-success');
                }, 10);
                setTimeout(() => {
                    window.onbeforeunload = null;
                    location.href = error.data.edit.redirectTarget;
                }, 3000);
            }
        }
    };
}
mw.hook( 've.activationComplete' ).add( onActivation );