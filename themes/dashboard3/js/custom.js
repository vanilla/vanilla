/*!
 * Dashboard 2016 - A new dashboard design for Vanilla.
 *
 * @author    Becky Van Bussel <beckyvanbussel@gmail.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 */

'use strict';

(function($) {
    $(document).on('change', '.js-file-upload', function() {
        var filename = $(this).val();
        if (filename.substring(3,11) == 'fakepath' ) {
            filename = filename.substring(12);
        }
        if (filename) {
            $(this).parent().find('.file-upload-choose').html(filename);
        }
    });

    $(document).ready(function() {
        // Pretty print
        $('#Pockets td:nth-child(4)').each(function() {
            var html = $(this).html();
            $(this).html('<pre class="prettyprint lang-html" style="white-space: pre-wrap;">' + html + '</pre>');
        });
        $('pre').addClass('prettyprint lang-html');
        console.log($('.prettyprint'));
        prettyPrint();

        $('#Form_Body').addClass('js-code-input js-mode-html js-height-200');
        ace.start();

    });

})(jQuery);


var ace = {
    start: function() {
        $('.js-code-input').each(function () {
            ace.makeAceTextArea($(this));
        });
    },

    makeAceTextArea: function (textarea) {
        var classList = textarea.attr('class').split(/\s+/);
        var mode = 'html';
        var height = 400;
        $.each(classList, function(index, item) {
            if (item.substr(0,7) === 'js-mode') {
                mode = item.substr(8, item.length);
            }
            if (item.substr(0,9) === 'js-height') {
                height = item.substr(10, item.length);
            }
        });
        var modes = ['html', 'css'];
        if (modes.indexOf(mode) === -1) {
            mode = 'html';
        }
        var formID = textarea.attr('id');
        textarea.before('<div id="editor-' + formID + '" style="height: ' + height + 'px;"></div>');
        textarea.hide();

        var editor = ace.edit("editor-" + formID);

        editor.getSession().setMode("ace/mode/" + mode);
        editor.getSession().setValue(textarea.val());
        editor.getSession().on('change', function () {
            textarea.val(editor.getSession().getValue());
        });

        textarea.val(editor.getSession().getValue());
    }
}
