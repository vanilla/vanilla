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

        // var imageUploadEnabled = $('#Form_ImageUpload-dot-Limits-dot-Enabled');
        // var imageUploadDimensions = $('.ImageUploadLimitsDimensions');
        //
        // if (imageUploadEnabled.prop("checked") === true ) {
        //     imageUploadDimensions.removeClass('dimensionsDisabled');
        // }
        //
        // imageUploadEnabled.on('click', function (e) {
        //     if (imageUploadEnabled.prop("checked") === true ) {
        //         imageUploadDimensions.removeClass('dimensionsDisabled');
        //     } else {
        //         imageUploadDimensions.addClass('dimensionsDisabled');
        //     }
        // });
    });

})(jQuery);
