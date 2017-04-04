/**
 * Content Module Scripts for In-Content Editing in Phire CMS 3
 */

phire.pageEditorOpened = false;

phire.launchPageEditor = function(href) {
    if (phire.pageEditorOpened) {
        $('#phire-in-edit-iframe').remove();
        //$('#phire-in-edit-iframe').fade(0, {
        //    "tween"    : 40,
        //    "speed"    : 200,
        //    "complete" : function() {
        //        $('#phire-in-edit-iframe').remove();
        //    }
        //});
        window.top.location.reload();
        phire.pageEditorOpened = false;
    } else {
        $('body').append('iframe', {id: 'phire-in-edit-iframe'});
        $('#phire-in-edit-iframe').css({'opacity': 0, 'border': 'none'});
        $('#phire-in-edit-iframe').attrib('src', href);
        $('#phire-in-edit-iframe').fade(100, {
            "tween"    : 40,
            "speed"    : 200
        });
        phire.pageEditorOpened = true;
    }
};

window.closePageEditor = function() {
    jax('#phire-in-edit-iframe').remove();
    //$('#phire-in-edit-iframe').fade(0, {
    //    "tween"    : 40,
    //    "speed"    : 200,
    //    "complete" : function() {
    //        jax('#phire-in-edit-iframe').remove();
    //    }
    //});
    window.top.location.reload();
    phire.pageEditorOpened = false;
};