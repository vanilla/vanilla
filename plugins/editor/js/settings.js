(function ($) {

    $(document).on('contentLoad', function () {

        var forceFormat = 'wysiwyg',
            inputFormatter = $('#Form_Garden-dot-InputFormatter'),
            forceWysiwyg = $('.form-group.forceWysiwyg');

        if (inputFormatter.length > 0) {
            $(forceWysiwyg).addClass('forceWysiwygDisabled');

            if (inputFormatter.val().toLowerCase() === forceFormat) {
                forceWysiwyg.removeClass('forceWysiwygDisabled');
            }

            inputFormatter.on('change', function (e) {
                $(forceWysiwyg).addClass('forceWysiwygDisabled');

                var selected = $(this).attr('id');
                selected = $('#' + selected + ' :selected').text();

                if (selected.toLowerCase() === forceFormat) {
                    forceWysiwyg.removeClass('forceWysiwygDisabled');
                }
            });
        }
    });

})(jQuery);
