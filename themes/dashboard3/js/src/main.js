/*!
 * Dashboard 2016 - A new dashboard design for Vanilla.
 *
 * @author    Becky Van Bussel <beckyvanbussel@gmail.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 */

'use strict';

(function($) {

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
        $('.navbar', element).scrollToFixed({
            zIndex: 1005
        });
    }

    function userDropDownInit(element) {
        var html = $('.js-dashboard-user-dropdown').html();
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
        })
    }

    function collapseInit(element) {
        var active = $('.js-nav-collapsible a.active', element);
        var collapsible = active.parents('.collapse');
        collapsible.addClass('in');
        $('a[href=#' + collapsible.attr('id') + ']').attr('aria-expanded', 'true');
    }

    $(document).on('contentLoad', function(e) {
        prettyPrintInit(e.target);
        aceInit(e.target);
        collapseInit(e.target);
        scrollToFixedInit(e.target);
        userDropDownInit(e.target);
    });

    $(document).on('click', '.js-clear-search', function() {
        $(this).parent('.search-wrap').find('input').val('');
    });

    $(document).on('shown.bs.collapse', function(e) {
        console.log(e);
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

})(jQuery);
