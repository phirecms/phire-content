/**
 * Content Module Scripts
 */

phire.batchCount        = 1;
phire.contentForm       = null;
phire.categoryForm      = null;
phire.contentParentId   = 0;
phire.contentParentUri  = '';
phire.categoryParentId  = 0;
phire.categoryParentUri = '';

phire.slug = function(src, tar) {
    if ((src != null) && (tar != null)) {
        var uri = new jax.String(jax('#' + src).val());
        jax('#' + tar).val(uri.slug());
    }

    if (jax('#uri-span')[0] != undefined) {
        if (jax('#parent_id')[0] != undefined) {
            var parent = jax('#parent_id').val();
            if (parent != phire.contentParentId) {
                phire.contentParentId = parent;
                var j = jax.json.parse('../json/' + parent);
                phire.contentParentUri = j.uri;
            }
        }
        var val = jax('#' + tar).val();
        if ((val.substring(0, 4) != 'http') && (val.substring(0, 1) != '/')) {
            val = phire.contentParentUri + val;
            if ((val != '') && (val.substring(0, 1) != '/')) {
                val = '/' + val;
            } else if (val == '') {
                val = '/';
            }
        }
        jax('#uri-span').val(((val.substring(0, 2) == '//') ? val.substring(1) : val));
    }
};

phire.catSlug = function(src, tar) {
    if ((src != null) && (tar != null)) {
        var uri = new jax.String(jax('#' + src).val());
        jax('#' + tar).val(uri.slug());
    }

    if (jax('#slug-span')[0] != undefined) {
        if (jax('#parent_id')[0] != undefined) {
            var parent = jax('#parent_id').val();
            if (parent != phire.categoryParentId) {
                phire.categoryParentId = parent;
                var jsonLoc = (window.location.href.indexOf('edit') != -1) ? '../json/' : './json/';
                var j = jax.json.parse(jsonLoc + parent);
                phire.categoryParentUri = j.uri;
            }
        }
        var val = jax('#' + tar).val();
        val = phire.categoryParentUri + val;
        if ((val != '') && (val.substring(0, 1) != '/')) {
            val = '/' + val;
        } else if (val == '') {
            val = '/';
        }
        jax('#slug-span').val(((val.substring(0, 2) == '//') ? val.substring(1) : val));
    }
};

phire.addBatchFields = function(max) {
    if (phire.batchCount < max) {
        phire.batchCount++;

        // Add file name field
        jax('#file_name_1').clone({
            "name" : 'file_name_' + phire.batchCount,
            "id"   : 'file_name_' + phire.batchCount
        }).appendTo(jax('#file_name_1').parent());

        // Add file title field
        jax('#file_title_1').clone({
            "name" : 'file_title_' + phire.batchCount,
            "id"   : 'file_title_' + phire.batchCount
        }).appendTo(jax('#file_title_1').parent());
    }
};

phire.processForm = function(response) {
    var j = jax.json.parse(response.text);
    if (j.updated != undefined) {
        if (j.redirect != undefined) {
            window.location.href = j.redirect;
        } else {
            if ((j.uri != undefined) && (jax('#quick-view')[0] != undefined)) {
                jax('#quick-view > a').attrib('href', phire.basePath + j.uri);
            }

            // If there is a history field
            if ((j.form != undefined) && (jax('#' + j.form)[0] != undefined)) {
                var frm = jax('#' + j.form)[0];
                if (frm.elements.length > 0) {
                    for (var name in frm.elements) {
                        if (name.indexOf('history_') != -1) {
                            var ids = name.split('_');
                            if (ids.length == 3) {
                                if (jax('#field_' + ids[2])[0] != undefined) {
                                    phire.curValue = jax('#field_' + ids[2]).val();
                                }
                                var h = jax.json.parse(phire.basePath + phire.appUri + '/structure/fields/json/history/' + ids[1] + '/' + ids[2]);
                                var hisSelOptions = jax('#' + name + ' > option');
                                var start = hisSelOptions.length - 1;
                                for (var i = start; i >= 0; i--) {
                                    jax(hisSelOptions[i]).remove();
                                }
                                jax('#' + name).append('option', {"value" : '0'}, '(' + phire.i18n.t('Current') + ')');
                                for (var i = 0; i < h.length; i++) {
                                    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    var dte     = new Date(h[i] * 1000);
                                    var month   = months[dte.getMonth()];
                                    var day     = dte.getDate();
                                    var year    = dte.getFullYear();
                                    var hours   = dte.getHours();
                                    var minutes = dte.getMinutes();
                                    var seconds = dte.getSeconds();

                                    if (day < 10) {
                                        day = '0' + day;
                                    }
                                    if (hours < 10) {
                                        hours = '0' + hours;
                                    }
                                    if (minutes < 10) {
                                        minutes = '0' + minutes;
                                    }
                                    if (seconds < 10) {
                                        seconds = '0' + seconds;
                                    }
                                    var dateFormat = month + ' ' + day + ', ' + year + ' ' + hours + ':' + minutes + ':' + seconds;
                                    jax('#' + name).append('option', {"value" : h[i]}, dateFormat);
                                }
                            }
                        }
                    }
                }
            }

            if (jax('#result')[0] != undefined) {
                jax('#result').css({
                    "background-color" : '#dbf2bf',
                    "color"            : '#315900',
                    "opacity"          : 0
                });
                jax('#result').val(phire.i18n.t('Saved') + '!');
                for (var i = 1; i <= phire.curErrors; i++) {
                    if (jax('#error-' + i)[0] != undefined) {
                        jax('#error-' + i).remove();
                    }
                }
                if (jax('#updated')[0] != undefined) {
                    jax('#updated').val(j.updated);
                }
                if ((j.form != undefined) && (jax('#' + j.form)[0] != undefined)) {
                    var f = jax('#' + j.form)[0];
                    for (var i = 0; i < f.elements.length; i++) {
                        if ((f.elements[i].type == 'text') || (f.elements[i].type == 'textarea')) {
                            f.elements[i].defaultValue = f.elements[i].value;
                        }
                    }
                    if (typeof CKEDITOR !== 'undefined') {
                        for (ed in CKEDITOR.instances) {
                            CKEDITOR.instances[ed].setData(f.elements[ed].value);
                        }
                    } else if (typeof tinymce !== 'undefined') {
                        for (ed in tinymce.editors) {
                            if (ed.indexOf('field_') != -1) {
                                tinymce.editors[ed].setContent(f.elements[ed].value);
                            }
                        }
                    }
                }
                jax('#result').fade(100, {tween : 10, speed: 200});
                phire.clear = setTimeout(phire.clearStatus, 3000);
            }
        }
    } else {
        if (jax('#result')[0] != undefined) {
            jax('#result').css({
                "background-color" : '#e8d0d0',
                "color"            : '#8e0202',
                "opacity"          : 0
            });
            jax('#result').val(phire.i18n.t('Please correct the errors below.'));
            for (var i = 1; i <= phire.curErrors; i++) {
                if (jax('#error-' + i)[0] != undefined) {
                    jax('#error-' + i).remove();
                }
            }
            jax('#result').fade(100, {tween : 10, speed: 200});
            phire.clear = setTimeout(phire.clearStatus, 3000);
        }
        for (name in j) {
            // Check if the error already exists via a PHP POST
            var curErrorDivs = jax('#' + name).parent().getElementsByTagName('div');
            var curErrorDivsHtml = [];
            for (var i = 0; i < curErrorDivs.length; i++) {
                curErrorDivsHtml.push(curErrorDivs[i].innerHTML);
            }
            // If error doesn't exists yet, append it
            if (curErrorDivsHtml.indexOf(j[name].toString()) == -1) {
                phire.curErrors++;
                jax(jax('#' + name).parent()).append('div', {"id" : 'error-' + phire.curErrors, "class" : 'error'}, j[name]);
            }

        }
    }
};

phire.loadEditor = function(editor, id) {
    if (null != id) {
        var w = Math.round(jax('#field_' + id).width());
        var h = Math.round(jax('#field_' + id).height());
        phire.selIds = [{ "id" : id, "width" : w, "height" : h }];
    }

    if (phire.selIds.length > 0) {
        for (var i = 0; i < phire.selIds.length; i++) {
            if (editor == 'ckeditor') {
                if (CKEDITOR.instances['field_' + phire.selIds[i].id] == undefined) {
                    CKEDITOR.replace(
                        'field_' + phire.selIds[i].id,
                        {
                            width                         : '100%',
                            height                        : phire.selIds[i].height,
                            allowedContent                : true,
                            filebrowserBrowseUrl          : phire.sysBasePath + '/content/browser/file?editor=ckeditor',
                            filebrowserImageBrowseUrl     : phire.sysBasePath + '/content/browser/image?editor=ckeditor',
                            filebrowserImageBrowseLinkUrl : phire.sysBasePath + '/content/browser/file?editor=ckeditor',
                            filebrowserWindowWidth        : '960',
                            filebrowserWindowHeight       : '720'
                        }
                    );
                }
            } else if (editor == 'tinymce') {
                if (tinymce.editors['field_' + phire.selIds[i].id] == undefined) {
                    tinymce.init(
                        {
                            selector              : "textarea#field_" + phire.selIds[i].id,
                            theme                 : "modern",
                            plugins: [
                                "advlist autolink lists link image hr", "searchreplace wordcount code fullscreen",
                                "table", "template paste textcolor"
                            ],
                            image_advtab          : true,
                            toolbar1              : "insertfile undo redo | styleselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link image",
                            width                 : '100%',
                            height                : phire.selIds[i].height,
                            relative_urls         : false,
                            convert_urls          : 0,
                            remove_script_host    : 0,
                            file_browser_callback : function(field_name, url, type, win) {
                                tinymce.activeEditor.windowManager.open({
                                    title  : "Asset Browser",
                                    url    : phire.sysBasePath + '/content/browser/' + type + '?editor=tinymce',
                                    width  : 960,
                                    height : 720
                                }, {
                                    oninsert : function(url) {
                                        win.document.getElementById(field_name).value = url;
                                    }
                                });
                            }
                        }
                    );
                } else {
                    tinymce.get('field_' + phire.selIds[i].id).show();
                }
            }
        }
    }
};


/**
 * Document ready function for Phire
 */
jax(document).ready(function(){
    // For content form
    if (jax('#content-form')[0] != undefined) {
        if (jax('#uri').attrib('type') == 'text') {
            phire.contentForm = jax('#content-form').form({
                "content_title" : {
                    "required" : true
                }
            });
            phire.contentForm.submit(function(){
                phire.submitted = true;
                return phire.contentForm.validate();
            });
        } else if (jax('#current-file')[0] == undefined) {
            phire.contentForm = jax('#content-form').form({
                "uri" : {
                    "required" : phire.i18n.t('The file field is required.')
                }
            });
            phire.contentForm.submit(function(){
                phire.submitted = true;
                return phire.contentForm.validate();
            });
        } else {
            phire.contentForm = jax('#content-form').form();
            phire.contentForm.submit(function(){
                phire.submitted = true;
            });
        }

        if (jax('#uri')[0] != undefined) {
            var val = jax('#uri').val();
            if ((jax('#parent_id')[0] != undefined) && (val.substring(0, 4) != 'http') && (val.substring(0, 1) != '/')) {
                var parent = jax('#parent_id').val();
                if (parent != phire.contentParentId) {
                    phire.contentParentId = parent;
                    var j = jax.json.parse('../json/' + parent);
                    phire.contentParentUri = j.uri;
                    val = phire.contentParentUri + jax('#uri').val();
                }
            }
            if (jax('#uri')[0].type != 'file') {
                if ((val.substring(0, 4) != 'http') && (val.substring(0, 1) != '/')) {
                    if ((val != '') && (val.substring(0, 1) != '/')) {
                        val = '/' + val;
                    } else if (val == '') {
                        val = '/';
                    }
                }
                jax(jax('#uri').parent()).append('span', {"id" : 'uri-span'}, ((val.substring(0, 2) == '//') ? val.substring(1) : val));
            }
        }

        phire.curForm = '#content-form';
        jax.beforeunload(phire.checkFormChange);
    }

    // For content type form
    if (jax('#content-type-form')[0] != undefined) {
        var contentTypeForm = jax('#content-type-form').form({
            "name" : {
                "required" : true
            }
        });

        contentTypeForm.setErrorDisplay(phire.errorDisplay);
        contentTypeForm.submit(function(){
            return contentTypeForm.validate();
        });
    }

    // For navigation form
    if (jax('#navigation-form')[0] != undefined) {
        var navigationForm = jax('#navigation-form').form({
            "navigation" : {
                "required" : true
            }
        });

        navigationForm.setErrorDisplay(phire.errorDisplay);
        navigationForm.submit(function(){
            return navigationForm.validate();
        });
    }

    // For template form
    if (jax('#template-form')[0] != undefined) {
        var templateForm = jax('#template-form').form({
            "name" : {
                "required" : true
            },
            "template" : {
                "required" : true
            }
        });

        templateForm.setErrorDisplay(phire.errorDisplay);
        templateForm.submit(function(){
            return templateForm.validate();
        });
    }

    // For category form
    if (jax('#category-form')[0] != undefined) {
        phire.categoryForm = jax('#category-form').form({
            "category_title" : {
                "required" : 'The title field is required.'
            }
        });

        phire.categoryForm.setErrorDisplay(phire.errorDisplay);
        phire.categoryForm.submit(function(){
            return phire.categoryForm.validate();
        });

        if (jax('#slug')[0] != undefined) {
            var val = '';
            if (jax('#parent_id')[0] != undefined) {
                var parent = jax('#parent_id').val();
                if (parent != phire.categoryParentId) {
                    phire.categoryParentId = parent;
                    var jsonLoc = (window.location.href.indexOf('edit') != -1) ? '../json/' : './json/';
                    var j = jax.json.parse(jsonLoc + parent);
                    phire.categoryParentUri = j.uri;
                    val = phire.categoryParentUri + jax('#slug').val();
                } else {
                    val = jax('#slug').val();
                }
            }
            if ((val != '') && (val.substring(0, 1) != '/')) {
                val = '/' + val;
            } else if (val == '') {
                val = '/';
            }
            jax(jax('#slug').parent()).append('span', {"id" : 'slug-span'}, ((val.substring(0, 2) == '//') ? val.substring(1) : val));
        }
    }

    if (jax('#content-remove-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#content-remove-form').checkAll(this.value);
            } else {
                jax('#content-remove-form').uncheckAll(this.value);
            }
        });
        jax('#content-remove-form').submit(function(){
            if (jax('#content-process').val() == -2) {
                return jax('#content-remove-form').checkValidate('checkbox', true);
            } else {
                return true;
            }
        });
    }
    if (jax('#category-remove-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#category-remove-form').checkAll(this.value);
            } else {
                jax('#category-remove-form').uncheckAll(this.value);
            }
        });
        jax('#category-remove-form').submit(function(){
            return jax('#category-remove-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#content-type-remove-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#content-type-remove-form').checkAll(this.value);
            } else {
                jax('#content-type-remove-form').uncheckAll(this.value);
            }
        });
        jax('#content-type-remove-form').submit(function(){
            return jax('#content-type-remove-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#template-remove-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#template-remove-form').checkAll(this.value);
            } else {
                jax('#template-remove-form').uncheckAll(this.value);
            }
        });
        jax('#template-remove-form').submit(function(){
            return jax('#template-remove-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#site-migration-form')[0] != undefined) {
        jax(jax('#site_from').parent()).attrib('class', 'blue-arrow');
    }
});
