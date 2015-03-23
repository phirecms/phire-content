/**
 * Content Module Scripts for Phire CMS 2
 */

phire.changeUri = function() {
    var slug = jax('#slug').val();
    var uri  = '';

    if ((jax('#parent_id').val() != '----') && (jax.cookie.load('phire') != '')) {
        var phireCookie = jax.cookie.load('phire');
        var path = phireCookie.base_path + phireCookie.app_uri;
        var json = jax.get(path + '/content/json/' + jax('#parent_id').val());
        uri = json.parent_uri;
    }

    if (slug != '') {
        uri = uri + '/' + slug;
    }

    jax('#uri').val(uri);
    jax('#uri-span').val(uri);

    return false;
};

jax(document).ready(function(){
    if (jax('#contents-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#contents-form').checkAll(this.value);
            } else {
                jax('#contents-form').uncheckAll(this.value);
            }
        });
        jax('#contents-form').submit(function(){
            if (jax('#content_process_action').val() == '-3') {
                return jax('#contents-form').checkValidate('checkbox', true);
            } else {
                return true;
            }
        });
    }
    if (jax('#content-types-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#content-types-form').checkAll(this.value);
            } else {
                jax('#content-types-form').uncheckAll(this.value);
            }
        });
        jax('#content-types-form').submit(function(){
            return jax('#content-types-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#content-form')[0] != undefined) {
        if (jax('#uri').val() != '') {
            jax('#uri-span').val(jax('#uri').val());
        }
    }
});
