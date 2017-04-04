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
        var json = jax.http.get(path + '/content/json/' + phire.currentParentId);
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
    $('#uri-span')[0].innerHTML = uri;

    return false;
};

phire.createSlug = function(text, field) {
    var slg    = '';
    var tmpSlg = '';
    var sep    = '/';

    if (text.length > 0) {
        if (sep != null) {
            var slgAry = [];
            var urlAry = text.split(sep);
            for (var i = 0; i < urlAry.length; i++) {
                tmpSlg = urlAry[i].toLowerCase();
                tmpSlg = tmpSlg.replace(/\&/g, 'and').replace(/([^a-zA-Z0-9 \-\/])/g, '')
                    .replace(/ /g, '-').replace(/-*-/g, '-');
                slgAry.push(tmpSlg);
            }
            tmpSlg = slgAry.join('/');
            tmpSlg = tmpSlg.replace(/-\/-/g, '/').replace(/\/-/g, '/').replace(/-\//g, '/');
            slg += tmpSlg;
        } else {
            tmpSlg = text.toLowerCase();
            tmpSlg = tmpSlg.replace(/\&/g, 'and').replace(/([^a-zA-Z0-9 \-\/])/g, '')
                .replace(/ /g, '-').replace(/-*-/g, '-');
            slg += tmpSlg;
            slg = slg.replace(/\/-/g, '/');
        }
        if (slg.lastIndexOf('-') == (slg.length - 1)) {
            slg = slg.substring(0, slg.lastIndexOf('-'));
        }
    }

    $(field).val(slg);

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
            console.log($('#uri').val());
            $('#uri-span')[0].innerHTML = $('#uri').val();
        }
        //phire.currentForm = '#content-form';
        //$('#content-form').submit(function(){
        //    phire.submitted = true;
        //});
        //if (jax.query('in_edit') == undefined) {
        //    jax.beforeunload(phire.checkFormChange);
        //}

        jax.calendar(['#publish-calendar', '#publish_date'], '#publish_date', {"fade" : 250});
        jax.calendar(['#expire-calendar', '#expire_date'], '#expire_date', {"fade" : 250});
    }
});
