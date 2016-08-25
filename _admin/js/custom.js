var jQ = jQuery.noConflict();
var Admin = {
    dataTableInstance: null,
    editableBlocks: ['data-admin-page', 'data-admin-pages', 'data-admin-element', 'data-admin-elements', 'data-admin-block'],
    callback: '',
    confirmDelete: false,
    dateTimePickerConfig: {format: 'Y-m-d H:i:00', mask: "", lang: 'ru', step: 30, dayOfWeekStart: 1},
    init: function () {
//        jQ('[' + Admin.editableBlocks.join('],[') + ']').addClass('adm__editable_block');

        Admin.scrollDocument();

        shortcut("Alt+1", function () {
            if (jQ.cookie('hideAdminBlocks')) {
                jQ.removeCookie('hideAdminBlocks');
                jQ(".adm__editable_block, .adm__panel, .adm__antipanel").show();

            } else {
                jQ.cookie('hideAdminBlocks', 1);
                jQ(".adm__editable_block, .adm__panel, .adm__antipanel").hide();
            }
        });


        //jQ(document).keydown(function(event) {
        //    if (event.keyCode == 119) { // F8
        //        event.preventDefault();
        //        if(jQ.cookie('hideAdminBlocks')){
        //            jQ.removeCookie('hideAdminBlocks');
        //            jQ(".adm__editable_block, .adm__panel, .adm__antipanel").show();
        //
        //        } else {
        //            jQ.cookie('hideAdminBlocks', 1);
        //            jQ(".adm__editable_block, .adm__panel, .adm__antipanel").hide();
        //        }
        //    }
        //});

        /**
         * Ajax Submit Forms functionality
         */
        jQ(document).on('submit', 'form.adm__ajax_form', function (e) {
            e.preventDefault();
            if (Admin.confirmDelete) {
                Admin.confirmDelete = false;
                if (!confirm("Удалить?")) {
                    return;
                }
            }
            tinyMCE.triggerSave();
            var form = jQ(this), action = form.attr('action');
            var formData = new FormData(form[0]);

            jQ.ajax({
                url: action,
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data) {
                        alert(data);
                    } else if (Admin.callback == 'reset') {
                        form.trigger('reset');
                        Admin.callback = '';
                    } else if (Admin.callback == 'redirect') {
                        window.location.href = '/';
                    } else if (Admin.callback == 'deleteElement') {
                        var elementId = form.find('input[name=id]').val();
                        if (elementId !== undefined) {
                            $('[data-admin-element=' + elementId + ']').fadeOut(2000, function () {
                                $(this).remove();
                            });
                        }
                    } else {
                        Admin.refreshPage();
                    }
                }
            });
        });

        jQ(document).on('submit', 'form.adm__datatable_form', function (e) {
            e.preventDefault();
            if (Admin.confirmDelete) {
                Admin.confirmDelete = false;
                if (!confirm("Удалить?")) {
                    return;
                }
            }
            tinyMCE.triggerSave();
            var form = jQ(this), action = form.attr('action');
            var formData = new FormData(form[0]);
            jQ.ajax({
                url: action,
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (Admin.callback == 'reset') {
                        form.trigger('reset');
                    } else if (Admin.callback == 'redirect') {
                        window.location.href = '/';
                    } else if (Admin.callback == 'refresh') {
                        Admin.dataTableInstance.fnDestroy(1);
                        jQ('.fancybox-inner').html(data);
                        Admin.dataTablesInit();
                    }
                    Admin.callback = '';
                }
            });
        });

        /**
         * Fancybox functionality
         */
        jQ('.fancybox').fancybox({
            title: null,
            wrapCSS: 'adm__modal',
            beforeShow: function () {
                Admin.tinyMCEInit();
                Admin.tinyMCESmallInit();
                Admin.dataTablesInit();
                Admin.dateTimePickerInit();
            },
            beforeClose: function () {
                tinymce.remove();
            },
            helpers: {
                overlay: {
                    locked: false
                }
            }
        });

        /**
         * Submit links functionality
         */
        jQ(document).on('click', '.adm__submit', function () {
            jQ(this).closest('form').submit();
        });

        jQ(window).bind("load", function () {
            setTimeout(function () {
                Admin.editableBlocksInit();
            }, 1000);
        });

    },
    dateTimePickerInit: function () {
        jQ('.adm__datetimepicker').datetimepicker().datetimepicker(Admin.dateTimePickerConfig);
//        $('.adm__datetimepicker').datetimepicker();
    },
    tinyMCEInit: function () {
        tinymce.init({
            mode: 'specific_textareas',
            editor_selector: 'mce__editor',
            content_css: [
                '/_admin/css/tinymce.content.css',
                '/css/bootstrap.min.css',
                '/css/style.css',
                '/css/color.css'
            ],
            height: 200,
            language: 'ru',
            theme: 'modern',
            fontsize_formats: "8px 9px 10px 11px 12px 14px 15px 16px 18px 26px 36px 40px",
            plugins: [
                'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
                'save table contextmenu directionality template paste textcolor responsivefilemanager'
            ],
            paste_as_text: true,
            paste_data_images: true,
            paste_word_valid_elements: "b,strong,i,em, ul, li, ol, li",
            toolbar: 'responsivefilemanager undo redo | styleselect | fontselect | fontsizeselect | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code',
            image_advtab: true,
            relative_urls: false,
            object_resizing: false,
            external_filemanager_path: '/_admin/plugins/filemanager/',
            external_plugins: {"filemanager": "/_admin/plugins/filemanager/plugin.min.js"},
            filemanager_title: 'Файловый менеджер',

//            style_formats: [
//                {title: 'Bold text', inline: 'b'},
//                {title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
//                {title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
//                {title: 'headline', inline: 'p', classes: 'headline'},
//                {title: 'Example 2', inline: 'span', classes: 'example2'},
//                {title: 'Table styles'},
//                {title: 'Выделенный абзац', selector: 'p', classes: 'lead'},
//                {title: 'Список со стрелочками', selector: 'ul', classes: 'arrow-list'},
//                {title: 'Выделенный красным', selector: 'span', classes: 'text-primary'}
//            ],
            setup: function (editor) {
                editor.on('init', function (e) {
                    jQ.fancybox.update();
                });
            }
        });

    },
    tinyMCESmallInit: function () {
        tinymce.init({
            mode: 'specific_textareas',
            editor_selector: 'mce__editor_small',
            content_css: '/_admin/css/tinymce.content.css',
            height: 100,
            language: 'ru',
            theme: 'modern',
            fontsize_formats: "8px 9px 10px 11px 12px 14px 15px 16px 18px 26px 36px 40px",
            plugins: [
                'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
                'save table contextmenu directionality template paste textcolor responsivefilemanager'
            ],
            paste_as_text: true,
            paste_data_images: true,
            paste_word_valid_elements: "b,strong,i,em, ul, li, ol, li",
            toolbar: 'responsivefilemanager undo redo | styleselect | fontselect | fontsizeselect | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code',
            menubar: false,
            statusbar: false,
            image_advtab: true,
            relative_urls: false,
            object_resizing: false,
            external_filemanager_path: '/_admin/plugins/filemanager/',
            external_plugins: {"filemanager": "/_admin/plugins/filemanager/plugin.min.js"},
            filemanager_title: 'Файловый менеджер'
        });
    },
    editableBlocksInit: function () {
        jQ(".adm__editable_block").remove();
        jQ('[' + Admin.editableBlocks.join('],[') + ']').each(function () {
            var element = jQ(this);
            if (!element.is(":visible") || !Admin.isOnScreen(element)) return;
            var layer = jQ("<div></div>");
            for (n in Admin.editableBlocks) {
                layer.attr(Admin.editableBlocks[n], element.attr(Admin.editableBlocks[n]));
//                element.removeAttr(Admin.editableBlocks[n]);
            }
            var width = element.outerWidth();
            var height = element.outerHeight();
            var top = element.offset()['top'];
            var left = element.offset()['left'];
            var zIndex = 999;
            var position = 'absolute';
            element.parents().each(function () {
                if (jQ(this).css('position') == 'fixed') {
                    zIndex += 3;
                    position = 'fixed';
                    top -= jQ(document).scrollTop();
                    left -= jQ(document).scrollLeft();
                    return;
                }
            });
            if (element.find('[' + Admin.editableBlocks.join('],[') + ']').length) {
                zIndex -= 1;
            }
            layer.css({'width': width, 'height': height, 'top': top, 'left': left, 'position': position, 'z-index': zIndex});
            layer.addClass('adm__editable_block');
            layer.appendTo('body');

            if (jQ.cookie('hideAdminBlocks')) {
                jQ(".adm__editable_block, .adm__panel, .adm__antipanel").hide();
            }
        });

        try {
            jQ('[' + Admin.editableBlocks.join('],[') + ']').qtip({
                content: {
                    text: function (event, api) {
                        var el = jQ(this);
                        jQ.each(Admin.editableBlocks, function (key, attrName) {
                            var attrValue = el.attr(attrName);
                            if (attrValue !== undefined) {
                                jQ.ajax({url: '/_admin/ajax.php?' + attrName + '=' + encodeURIComponent(attrValue)})
                                    .done(function (html) {
                                        api.set('content.text', html);
                                        return true;
                                    })
                                    .fail(function (xhr, status, error) {
                                        api.set('content.text', status + ': ' + error);
                                        return false;
                                    });
                            }
                        });
                        return 'Loading...';
                    }
                },
                style: {
                    classes: 'qtip-light qtip-shadow adm__tooltip'
                },
                hide: {
                    fixed: true,
                    delay: 300
                },
                position: {
                    viewport: jQ(window)
                }
            });
        } catch (err) {
            console.log(err);
        }
    },
    dataTablesInit: function () {
        try {
            if(!jQ.fn.dataTable.isDataTable( '.adm__datatable' )){
                Admin.dataTableInstance = jQ('.adm__datatable').dataTable({
                    dom: 'T<"clear">lfrtip',
                    tableTools: {
                        "sSwfPath": "/_admin/swf/copy_csv_xls_pdf.swf"
                    },
                    language: {
                        "url": "/_admin/js/jquery.dataTables.russian.json"
                    },
                    "aLengthMenu": [
                        [100, 250, 500, 1000, -1],
                        [100, 250, 500, 1000, "ВСЕ"]
                    ],
                    "iDisplayLength": 100,
                    "aaSorting": [
                        [0, "desc"]
                    ]
                });
            }

        } catch (err) {
            console.log(err);
        }
    },
    addSubElement: function (el) {
        tinymce.remove();
        var dtp = 0;
        var wrapper = jQ(el).parents('.adm__sub_element_wrapper');
        var clone = wrapper.children('.adm__sub_element').last().clone();
        clone.find('input, textarea').each(function () {
            var clone = jQ(this);
            if (clone.hasClass('adm__datetimepicker')) {
                dtp = 1;
                clone.datetimepicker().datetimepicker(Admin.dateTimePickerConfig);
            }
            clone.attr('name', clone.attr('name').replace(/(.*?\[)(\d+)(.*)/, function (match, str1, str2, str3) {
                return str1 + (parseInt(str2) + 1) + str3;
            })).attr('id', clone.attr('name'));
        });
        clone.appendTo(wrapper);
        if (dtp) {
            Admin.dateTimePickerInit();
        }
        Admin.tinyMCESmallInit();
    },
    removeSubElement: function (el) {
        jQ(el).parents('.adm__sub_element').remove();
    },
    refreshSelects: function (el) {
        jQ(el).siblings("select[name='node_text2']").attr('name', '').hide();
        jQ(el).siblings("select[data-name='" + jQ(el).val() + "']").attr('name', 'node_text2').show();
    },
    refreshPage: function () {
        jQ.cookie('posY', jQ(document).scrollTop());
        location.reload(1);
    },
    scrollDocument: function () {
        if (jQ.cookie('posY') != undefined) {
            jQ('html, body').scrollTop(parseInt(jQ.cookie('posY')));
            jQ.removeCookie('posY');
        }
    },
    isOnScreen: function (element) {
        var win = $(window);
        var viewport = {
            top: win.scrollTop(),
            left: win.scrollLeft()
        };
        viewport.right = viewport.left + win.width();
        viewport.bottom = viewport.top + win.height();

        var bounds = element.offset();
        bounds.right = bounds.left + element.outerWidth();
        bounds.bottom = bounds.top + element.outerHeight();

        //return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
        return (!(viewport.right < bounds.right || viewport.left > bounds.left));

    }

};

jQ(document).ready(function () {
    Admin.init();
});

