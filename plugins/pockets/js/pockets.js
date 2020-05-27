/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// jQuery(document).ready(function($) {
$(document).on('contentLoad', function() {
    revealRepeatOptions();
    toggleRepeat();
});

$(document).on('change', 'input[name$=RepeatType]', function() {
    revealRepeatOptions();
});

$(document).on('change', 'select[name$=Location]', function() {
    toggleRepeat();
});

// Hide/show the appropriate repeat options.
var revealRepeatOptions = function() {
    // Get the current value of the repeat options.
    var selected = $("input[name$=RepeatType]:checked").val();
    switch (selected) {
        case 'every':
            $('.RepeatEveryOptions').show();
            $('.RepeatIndexesOptions').hide();
            break;
        case 'index':
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').show();
            break;
        default:
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').hide();
            break;
    }
};

var toggleRepeat = function() {
    var selected = $("select[name$=Location] option:selected").text();
    switch (selected) {
        case 'AfterBanner':
        case 'Custom':
            $('.js-repeat').hide();
            break;
        default:
            $('.js-repeat').show();
    }
}
