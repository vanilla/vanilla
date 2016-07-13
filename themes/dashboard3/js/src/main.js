/*!
 * Dashboard 2016 - A new dashboard design for Vanilla.
 *
 * @author    Becky Van Bussel <beckyvanbussel@gmail.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 */

'use strict';

(function($) {

    var dashboardSymbol =  function(name, alt, cssClass) {
        if (alt) {
            alt = 'alt="' + alt + '" ';
        } else {
            alt = '';
        }

        if (!cssClass) {
            cssClass = '';
        }

        return '<svg ' + alt + ' class="icon ' + cssClass + 'icon-svg-' + name + '" viewBox="0 0 17 17"><use xlink:href=\"#' + name + '" /></svg>';
    }

    var codeInput = {
        // Replaces any textarea with the 'js-code-input' class with an code editor.
        start: function(element) {
            $('.js-code-input', element).each(function () {
                codeInput.makeAceTextArea($(this));
            });
        },

        // Adds the 'js-code-input' class to a form and the mode and height data attributes.
        init: function(textarea, mode, height) {
            if (!textarea.length) {
                return;
            }
            textarea.addClass('js-code-input');
            textarea.data('code-input', {'mode': mode, 'height': height});
        },

        //
        makeAceTextArea: function (textarea) {
            var mode = textarea.data('code-input').mode;
            var height = textarea.data('code-input').height;
            var modes = ['html', 'css'];

            if (modes.indexOf(mode) === -1) {
                mode = 'html';
            }
            if (!height) {
                height = 400;
            }

            // Add the ace input before the actual textarea and hide the textarea.
            var formID = textarea.attr('id');
            textarea.before('<div id="editor-' + formID + '" style="height: ' + height + 'px;"></div>');
            textarea.hide();

            var editor = ace.edit('editor-' + formID);
            editor.getSession().setMode('ace/mode/' + mode);
            editor.setTheme('ace/theme/clouds');

            // Set the textarea value on the ace input and update the textarea when the ace input is updated.
            editor.getSession().setValue(textarea.val());
            editor.getSession().on('change', function () {
                textarea.val(editor.getSession().getValue());
            });
        }
    };

    var modal = {

        settings: {
            httpmethod: 'get'
        },

        contentDefaults: {
            cssClass: '',
            title: '',
            footer: '',
            closeIcon: '',
            body: '',
            form: {
                open: '',
                close: ''
            }
        },

        modalHtml: ' \
        <div><div class="modal-dialog {cssClass}" role="document"> \
            <div class="modal-content"> \
                <div class="modal-header"> \
                    <button type="button" class="btn-icon close" data-dismiss="modal" aria-label="Close"> \
                        {closeIcon} \
                    </button> \
                    <h4 class="modal-title">{title}</h4> \
                </div> \
                {form.open} \
                <div class="modal-body">{body}</div> \
                <div class="modal-footer">{footer}</div> \
                {form.close} \
            </div> \
        </div></div>',

        modalShell: '<div class="modal fade" id="{id}" tabindex="-1" role="dialog" aria-hidden="true"></div>',

        id: '',

        target: '',

        trigger: {},

        start: function($trigger, settings) {
            modal.trigger = $trigger;
            modal.settings = $.extend(true, settings, $trigger.data());
            console.log(modal.settings);
            modal.contentDefaults.closeIcon = dashboardSymbol('close');
            modal.id = Math.random().toString(36).substr(2, 9);
            modal.target = $trigger.attr('href');
            modal.setupTrigger($trigger);
            modal.addToDom();
            $('#' + modal.id).modal('show');
            if (modal.settings.modaltype === 'confirm') {
                modal.addConfirmContent();
            } else {
                modal.addContent();
            }
        },

        load: function() {
            modal.handleForm($('#' + modal.id));
        },

        setupTrigger: function($trigger) {
            $trigger.attr('data-target', '#' + modal.id);
            $trigger.attr('data-modal-id', modal.id);
        },

        handleConfirm: function() {
            // request the target via ajax
            var ajaxData = {'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON'};
            if (modal.settings.httpmethod === 'post') {
                ajaxData.TransientKey = gdn.definition('TransientKey');
            }

            $.ajax({
                method: (modal.settings.httpmethod === 'post') ? 'POST' : 'GET',
                url: modal.target,
                data: ajaxData,
                dataType: 'json',
                error: function(xhr) {
                    gdn.informError(xhr);
                },
                success: function(json) {
                    gdn.inform(json);
                    gdn.processTargets(json.Targets);
                    if (json.RedirectUrl) {
                        setTimeout(function() {
                            document.location.replace(json.RedirectUrl);
                        }, 300);
                    }
                    $('#' + modal.id).modal('hide');
                    modal.afterConfirmSuccess();
                }
            });
        },

        // Default is to remove the closest item with the class 'js-modal-item'
        afterConfirmSuccess: function() {
            var found = false;
            if (!settings.confirmaction || settings.confirmaction === 'delete') {
                found = modal.trigger.closest('.js-modal-item').length !== 0;
                modal.trigger.closest('.js-modal-item').remove();
            }

            if (!found) {
                document.location.replace(window.location.href);
            }
        },

        confirmContent: function() {
            // Replace language definitions
            var confirmHeading = gdn.definition('ConfirmHeading', 'Confirm');
            var confirmText = gdn.definition('ConfirmText', 'Are you sure you want to do that?');
            var ok = gdn.definition('Okay', 'Okay');
            var cancel = gdn.definition('Cancel', 'Cancel');

            var footer = '<button class="btn btn-primary btn-ok js-ok">' + ok + '</button>'
            footer += '<button class="btn btn-primary btn-cancel js-cancel">' + cancel + '</button>'

            return {
                title: confirmHeading,
                footer: footer,
                body: confirmText,
                cssClass: 'modal-sm'
            };
        },

        addConfirmContent: function() {
            $('#' + modal.id).htmlTrigger(modal.replaceHtml(modal.confirmContent()));
        },

        addContent: function() {
            var ajaxData = {
                'DeliveryType' : 'VIEW',
                'DeliveryMethod' : 'JSON'
            };

            $.ajax({
                method: 'GET',
                url: modal.target,
                data: ajaxData,
                dataType: 'json',
                error: function(request, textStatus, errorThrown) {
                    console.log('error: ');
                    console.log(request);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
                success: function(json) {
                    var body = json.Data;
                    var content = modal.parseBody(body);
                    $('#' + modal.id).htmlTrigger(modal.replaceHtml(content));
                }
            });
        },

        replaceHtml: function(parsedContent) {

            // Copy the defaults into the content array
            var content = {};
            $.extend(true, content, modal.contentDefaults);

            // Data attributes override parsed content, content overrides defaults.
            $.extend(true, parsedContent, modal.settings.content);
            $.extend(true, content, parsedContent);

            var html = modal.modalHtml;
            html = html.replace('{body}', content.body);
            html = html.replace('{cssClass}', content.cssClass);
            html = html.replace('{title}', content.title);
            html = html.replace('{closeIcon}', content.closeIcon);
            html = html.replace('{footer}', content.footer);
            html = html.replace('{form.open}', content.form.open);
            html = html.replace('{form.close}', content.form.close);

            return html;
        },

        parseBody: function(body) {
            var title = '';
            var footer = '';
            var formTag = '';
            var formCloseTag = '';
            var $elem = $('<div />').append($($.parseHTML(body + ''))); // Typecast html to a string and create a DOM node
            var $title = $elem.find('h1');
            var $footer = $elem.find('.Buttons');
            var $form = $elem.find('form');

            // Pull out the H1 block from the view to add to the modal title
            if ($title.length !== 0) {
                title = $title.html();
                $title.remove();
                body = $elem.html();
            }

            // Pull out the buttons from the view to add to the modal footer
            if ($footer.length !== 0) {
                footer = $footer.html();
                $footer.remove();
                body = $elem.html();
            }

            // Pull out the form opening and closing tags to wrap around the modal-content and modal-footer
            if ($form.length !== 0) {
                formCloseTag = '</form>';
                var formHtml = $form.prop('outerHTML');
                formTag = formHtml.split('>')[0] += '>';
                body = body.replace(formTag, '');
                body = body.replace('</form>', '');
            }

            return {
                title: title,
                footer: footer,
                body: body,
                form: {
                    open: formTag,
                    close: formCloseTag
                }
            };
        },

        addToDom: function() {
            $('body').append(modal.modalShell.replace('{id}', modal.id));
            modal.addEventListeners();
        },

        addEventListeners: function() {
            $('#' + modal.id).on('shown.bs.modal', function() {
                modal.load(this);
            });
            $('#' + modal.id).on('hidden.bs.modal', function() {
                $(this).remove();
            });
            $('#' + modal.id).on('click', '.js-ok', function() {
               modal.handleConfirm(this);
            });
            $('#' + modal.id).on('click', '.js-cancel', function() {
                $('#' + modal.id).modal('hide');
            });
        },

        handleForm: function(element) {
            $('form', element).ajaxForm({
                data: {
                    'DeliveryType': 'VIEW',
                    'DeliveryMethod': 'JSON'
                },
                dataType: 'json',
                success: function(json) {
                    gdn.inform(json);
                    gdn.processTargets(json.Targets);

                    if (json.FormSaved === true) {
                        modal.afterFormSuccess(json.RedirectUrl);
                        $('#' + modal.id).modal('hide');
                    } else {
                        var body = json.Data;
                        var content = modal.parseBody(body);
                        $('#' + modal.id + ' .modal-body').htmlTrigger(content.body);
                    }
                },
                error: function(xhr) {
                    gdn.informError(xhr);
                }
            });
        },

        afterFormSuccess: function(redirectUrl) {
            if (redirectUrl) {
                setTimeout(function() {
                    document.location.replace(redirectUrl);
                }, 300);
            } else {
                document.location.replace(window.location.href);
            }
        }

    };

    function prettyPrintInit(element) {
        // Pretty print
        $('#Pockets td:nth-child(4)', element).each(function () {
            var html = $(this).html();
            $(this).html('<pre class="prettyprint lang-html" style="white-space: pre-wrap;">' + html + '</pre>');
        });
        $('pre', element).addClass('prettyprint lang-html');
        prettyPrint();
    }

    function aceInit(element) {
        // Editor classes
        codeInput.init($('.pockets #Form_Body', element), 'html', 200);
        codeInput.init($('#Form_CustomHtml', element), 'html', 800);
        codeInput.init($('#Form_CustomCSS', element), 'css', 800);
        codeInput.start(element);
    }

    function scrollToFixedInit(element) {
        if ($('.js-scroll-to-fixed', element).length) {
            var panelPadding = Number($('.panel').css('padding-top').substring(0, $('.panel').css('padding-top').length - 2));
            var minOffset = $('.navbar').outerHeight(true) + panelPadding;

            $('.js-scroll-to-fixed > *:first-child').css('margin-top', 0); // Prevent jank on items with a margin-top.
            $('.js-scroll-to-fixed > *:first-child > *:first-child').css('margin-top', 0); // Prevent jank on items with a margin-top.

            $('.js-scroll-to-fixed', element).each(function () {
                $(this).scrollToFixed({
                    zIndex: 1000,
                    marginTop: function () {
                        var marginTop = $(window).height() - $(this).outerHeight(true) - minOffset;
                        if (marginTop >= 0) {
                            return minOffset;
                        }
                        return marginTop;
                    }
                });
            });
        }

        $('.navbar').addClass('navbar-short');
        var navShortHeight = $('.navbar').outerHeight(true);
        $('.navbar').removeClass('navbar-short');
        var navHeight = $('.navbar').outerHeight(true);

        window.navOffset = navHeight - navShortHeight;

        $('.navbar', element).scrollToFixed({
            zIndex: 1005,
            spacerClass: 'js-scroll-to-fixed-spacer'
        });

        $('.js-scroll-to-fixed-spacer').height(navHeight);

        $('.modal-header', element).scrollToFixed({
            zIndex: 1005
        });

        $('.modal-footer', element).scrollToFixed({
            zIndex: 1005
        });
    }

    $(window).scroll(function() {
        var offset = window.navOffset; // Height difference between short and normal navbar.
        if ($(window).scrollTop() > offset) {
            $('.navbar').addClass('navbar-short');
        } else {
            $('.navbar').removeClass('navbar-short');
        }
    });

    function userDropDownInit(element) {
        var html = $('.js-dashboard-user-dropdown').html();
        if ($('.navbar .js-card-user', element).length !== 0) {
            new Drop({
                target: document.querySelector('.navbar .js-card-user', element),
                content: html,
                constrainToWindow: true,
                remove: true,
                tetherOptions: {
                    attachment: 'top right',
                    targetAttachment: 'bottom right',
                    offset: '-10 0'
                }
            });
        }
    }

    function collapseInit(element) {
        var active = $('.js-nav-collapsible a.active', element);
        var collapsible = active.parents('.collapse');
        collapsible.addClass('in');
        $('a[href=#' + collapsible.attr('id') + ']').attr('aria-expanded', 'true');
    }



    function clipboardInit() {
        var clipboard = new Clipboard('.btn-copy');

        clipboard.on('success', function(e) {
            var tooltip = $(e.trigger).tooltip({
                show: true,
                placement: 'bottom',
                title: $(e.trigger).attr('data-success-text'),
                trigger: 'manual',
            });
            tooltip.tooltip('show');
            setTimeout(function() {
                tooltip.tooltip('hide')
            }, '2000')
        });

        clipboard.on('error', function(e) {
            console.log(e);
        });
    }

    function drawerInit(element) {
        $('.panel-left', element).drawer({
            toggle    : '.js-panel-left-toggle'
            , container : '.main-container'
            , content   : '.main-row .main'
        });
    }

    function icheckInit(element) {
        $('input:not(.label-selector-input):not(.toggle-input)', element).iCheck({
            aria: true
        }).on('ifChanged', function(e) {
            $(this).trigger("change");
        });
    }

    function expanderInit(element) {
        $('.FeedDescription', element).expander({
            slicePoint: 65,
            normalizeWhitespace: true,
            expandText: gdn.definition('ExpandText'),
            userCollapseText: gdn.definition('CollapseText')
        });
    }

    $(document).on('contentLoad', function(e) {
        prettyPrintInit(e.target);
        aceInit(e.target);
        collapseInit(e.target);
        scrollToFixedInit(e.target);
        userDropDownInit(e.target);
        modal.load(e.target);
        clipboardInit();
        drawerInit(e.target);
        icheckInit(e.target);
        expanderInit(e.target);
    });

    // $(document).on('click', '.js-collapse-toggle', function() {
    //
    // });


    // Event handlers

    $(document).on('shown.bs.collapse', function() {
        $('.panel-nav .js-scroll-to-fixed').trigger('detach.ScrollToFixed');
        scrollToFixedInit($('.panel-nav'));
    });

    $(document).on('change', '.js-file-upload', function() {
        var filename = $(this).val();
        if (filename.substring(3, 11) === 'fakepath') {
            filename = filename.substring(12);
        }
        if (filename) {
            $(this).parent().find('.file-upload-choose').html(filename);
        }
    });

    $(document).on('click', '.js-modal, .Popup, .Popable', function(e) {
        e.preventDefault();
        modal.start($(this), {});
    });

    $(document).on('click', '.js-modal-confirm', function(e) {
        e.preventDefault();
        modal.start($(this), {
            modaltype: 'confirm'
        });
    });

})(jQuery);
