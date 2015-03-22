/**
 * Content Module Scripts for Phire CMS 2
 */

phire.changeUri = function() {

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
            return jax('#contents-form').checkValidate('checkbox', true);
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
