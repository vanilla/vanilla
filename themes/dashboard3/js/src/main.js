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
})(jQuery);
