/**
 * Content Module Scripts for In-Content Editing in Phire CMS 2
 */

phire.pageEditorOpened = false;

phire.launchPageEditor = function(href) {
    if (phire.pageEditorOpened) {
        jax('#phire-in-edit-iframe').fade(0, {
            "tween"    : 40,
            "speed"    : 200,
            "complete" : function() {
                jax('#phire-in-edit-iframe').remove();
            }
        });
        window.top.location.reload();
        phire.pageEditorOpened = false;
    } else {
        jax('body').append('iframe', {id: 'phire-in-edit-iframe'});
        jax('#phire-in-edit-iframe').css({'opacity': 0, 'border': 'none'});
        jax('#phire-in-edit-iframe').attrib('src', href);
        console.log(jax('#phire-in-edit-iframe').attrib('src'));
        jax('#phire-in-edit-iframe').fade(100, {
            "tween"    : 40,
            "speed"    : 200
        });
        phire.pageEditorOpened = true;
    }
};

window.closePageEditor = function() {
    jax('#phire-in-edit-iframe').fade(0, {
        "tween"    : 40,
        "speed"    : 200,
        "complete" : function() {
            jax('#phire-in-edit-iframe').remove();
        }
    });
    window.top.location.reload();
    phire.pageEditorOpened = false;
};