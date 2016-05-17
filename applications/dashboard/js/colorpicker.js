/**
 * Handles Gdn_Form's color input.
 * The input with the js-color-picker-value class stores the last valid hex value (the color shown in the preview span).
 *
 * This is the expected construction for the color picker (can be built using Gdn_Form's color() method):
 *
 * <div class="js-color-picker color-picker input-group">
 *     <input type="text" class="js-color-picker-value color-picker-value InputBox Hidden">
 *     <input type="text" class="js-color-picker-text color-picker-text InputBox">
 *     <span class="js-color-picker-preview color-picker-preview"></span>
 *     <input type="color" class="js-color-picker-color color-picker-color">
 * </div>
 */

/**
 * @type {{start: Function, isHex: Function, normalizeHex: Function}}
 */
var colorPicker = {
    /**
     * Starts the color picker javascript
     */
    start: function($input) {
        if ($input.find('.js-color-picker-value').val()) {
            var color = $input.find('.js-color-picker-value').val();
            $input.find('.js-color-picker-text').val(color);
            $input.find('.js-color-picker-preview').css('background-color', color);
        }

        // Selecting based on picker
        $input.find('.js-color-picker-color').change(function () {
            var color = $(this).val();
            $input.find('.js-color-picker-text').val(color);
            $input.find('.js-color-picker-preview').css('background-color', color);
            $input.find('.js-color-picker-value').val(color);
        });

        // Selecting based on text
        $input.find('.js-color-picker-text').on('input', function () {
            if (colorPicker.isHex($(this).val())) {
                color = colorPicker.normalizeHex($(this).val());
                $input.find('.js-color-picker-color').val(color);
                $input.find('.js-color-picker-preview').css('background-color', color);
                $input.find('.js-color-picker-value').val(color);
            }
        });

        // Trigger input color picker element on clicking preview area
        $input.find('.js-color-picker-preview').on('click', function () {
            $input.find('.js-color-picker-color').trigger('click');
            $input.find('.js-color-picker-color').val($input.find('.js-color-picker-value').val());
        });
    },

    /**
     * Tests whether we have a valid six or three-character hex code, with or without the opening hash.
     *
     * @param string The hex code to test.
     * @returns {boolean} Whether the hex code is valid.
     */
    isHex: function(string) {
        var regex = new RegExp(/(^#?[0-9A-F]{6}$)|(^#?[0-9A-F]{3}$)/i);
        if (regex.test(string)) {
            return true;
        }
        return false;
    },

    /**
     * Ensures a valid hex code begins with a hash and if expanded to its six-character equivalent.
     * Many system color pickers need the six-character hash value.
     *
     * @param color A valid six or three-character hex code, with or without the leading hash.
     * @returns {string} A six-character hex code with the leading hash.
     */
    normalizeHex: function(color) {
        if (!colorPicker.isHex(color)) {
            return '';
        }
        if (color.substr(0, 1) === '#') {
            color = color.substr(1);
        }
        if (color.length === 3) {
            color = color.substr(0, 1) + color.substr(0, 1)
            + color.substr(1, 1) + color.substr(1, 1)
            + color.substr(2, 1) + color.substr(2, 1);
        }
        color = '#' + color;
        return color;
    }
}

jQuery(document).ready(function($) {
    $('.js-color-picker').each(function() {
        colorPicker.start($(this));
    });
});
