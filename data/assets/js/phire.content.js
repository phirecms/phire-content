/**
 * Content Module Scripts for Phire CMS 3
 */

phire.currentParentId  = '----';
phire.currentParentUri = '';

phire.changeUri = function() {
    var slug = $('#slug').val();

    if (($('#content_parent_id').val() != phire.currentParentId) && (jax.cookie.load('phire') != '')) {
        phire.currentParentId = $('#content_parent_id').val();
        var phireCookie = jax.cookie.load('phire');
        var path = phireCookie.base_path + phireCookie.app_uri;
        var json = jax.get(path + '/content/json/' + phire.currentParentId);
        phire.currentParentUri = json.parent_uri;
    }

    var uri = phire.currentParentUri;

    if ((slug == '') && (uri == '')) {
        uri = '/';
    } else {
        if (uri == '/') {
            uri = uri + slug;
        } else {
            uri = uri + ((slug != '') ? '/' + slug : '');
        }
    }

    $('#uri').val(uri);
    $('#uri-span').val(uri);

    return false;
};

$(document).ready(function(){
    if ($('#contents-form')[0] != undefined) {
        $('#contents-form').submit(function(){
            if ($('#content_process_action').val() == '-3') {
                return confirm('This action cannot be undone. Are you sure?');
            } else {
                return true;
            }
        });
    }
    if ($('#content-form')[0] != undefined) {
        if ($('#uri').val() != '') {
            $('#uri-span').val($('#uri').val());
        }
        //phire.currentForm = '#content-form';
        //$('#content-form').submit(function(){
        //    phire.submitted = true;
        //});
        //if (jax.query('in_edit') == undefined) {
        //    jax.beforeunload(phire.checkFormChange);
        //}
        //$('#publish-calendar').calendar('#publish_date');
        //$('#publish_date').calendar('#publish_date');
        //$('#expire-calendar').calendar('#expire_date');
        //$('#expire_date').calendar('#expire_date');
    }
});
