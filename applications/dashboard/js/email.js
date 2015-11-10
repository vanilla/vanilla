// This file contains javascript that is specific to the garden/routes controller.
jQuery(document).ready(function($) {

    emailStyles.start();

    // Hide/reveal the smtp options when the UseSmtp checkbox is un/checked.
    $('#Form_Garden-dot-Email-dot-UseSmtp').click(function() {
        if ($(this).prop('checked')) {
            $('#SmtpOptions').slideDown('fast');
        } else {
            $('#SmtpOptions').slideUp('fast');
        }
    });
    // Hide onload if unchecked
    if ($('#Form_Garden-dot-Email-dot-UseSmtp').prop('checked')) {
        $('#SmtpOptions').show();
    } else {
        $('#SmtpOptions').hide();
    }
});

var emailStyles = {

    /**
     * Starts up our button event handlers.
     */
    start: function() {
        // Enforce hidden css class.
        $('.Hidden.Button').hide();

        // Get new banner image.
        $('a.UploadImage').popup({
            afterSuccess: emailStyles.reloadImage
        });

        // Ajax call to remove banner
        $('.js-remove-email-image-button').click(emailStyles.removeImage);
    },

    /**
     * No need for an extra save button when uploading an image. This removes one click from the equation.
     */
    submitImageForm: function() {
        $('.js-email-image-form').submit();
    },

    /**
     * Updates the email image and on success ensures the remove button and image are shown.
     */
    reloadImage: function() {
        $.ajax({
            type: 'GET',
            url: gdn.url('/dashboard/settings/emailimageurl'),
            success: function(json) {
                // Set image source
                $('.js-email-image').attr('src', json['EmailImage']);
                $('.js-email-image').show();
                $('.js-remove-email-image-button').show();
            }
        });
    },

    /**
     * Removes the email image and on success hides the remove button and image.
     */
    removeImage: function() {
        $.ajax({
            type: 'GET',
            url: gdn.url('/dashboard/settings/removeemailimage'),
            data: {tk: gdn.definition('TransientKey')},
            success: function() {
                $('.js-email-image').hide();
                $('.js-remove-email-image-button').hide();
            }
        });
    }
}
