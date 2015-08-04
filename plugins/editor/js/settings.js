jQuery(document).ready(function($) {

    var forceFormat = 'wysiwyg',
        inputFormatter = $('#Form_Garden-dot-InputFormatter'),
        forceWysiwyg = $('#Form_Plugins-dot-editor-dot-ForceWysiwyg').closest('li');

    $(forceWysiwyg).addClass('forceWysiwyg forceWysiwygDisabled');

    if (inputFormatter.val().toLowerCase() == forceFormat) {
        forceWysiwyg.removeClass('forceWysiwygDisabled');
    }

    inputFormatter.on('change', function(e) {
        $(forceWysiwyg).addClass('forceWysiwygDisabled');

        var selected = $(this).attr('id');
        selected = $('#' + selected + ' :selected').text();

        if (selected.toLowerCase() == forceFormat) {
            forceWysiwyg.removeClass('forceWysiwygDisabled');
        }
    });
});
