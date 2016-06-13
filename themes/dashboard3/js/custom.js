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
        start: function() {
            $('.js-code-input').each(function () {
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

    $(document).on('change', '.js-file-upload', function () {
        var filename = $(this).val();
        if (filename.substring(3, 11) === 'fakepath') {
            filename = filename.substring(12);
        }
        if (filename) {
            $(this).parent().find('.file-upload-choose').html(filename);
        }
    });

    $(document).on('contentLoad', function () {
        // Pretty print
        $('#Pockets td:nth-child(4)').each(function () {
            var html = $(this).html();
            $(this).html('<pre class="prettyprint lang-html" style="white-space: pre-wrap;">' + html + '</pre>');
        });
        $('pre').addClass('prettyprint lang-html');
        prettyPrint();

        // Editor classes
        codeInput.init($('.pockets #Form_Body'), 'html', 200);
        codeInput.init($('#Form_CustomHtml'), 'html', 800);
        codeInput.init($('#Form_CustomCSS'), 'css', 800);
        codeInput.start();

        var html = $('.js-dashboard-user-dropdown').html();
        new Drop({
            target: document.querySelector('.navbar .js-card-user'),
            content: html,
            constrainToWindow: true,
            tetherOptions: {
                attachment: 'top right',
                targetAttachment: 'top right',
                offset: '2rem 0'
            }
        });
    });







})(jQuery);
