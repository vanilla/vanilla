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
        $('.js-upload-email-image-button').popup({
            afterSuccess: emailStyles.reloadImage
        });

        // Ajax call to remove banner
        $('.js-remove-email-image-button').click(emailStyles.removeImage);

        // Ajax call for preview popup
        $('.js-email-preview-button').click(emailStyles.emailPreview);

        if ($('.ActivateSlider-Inactive').length > 0) {
            emailStyles.hideSettings();
        }
    },

    /**
     * No need for an extra save button when uploading an image. This removes one click from the equation.
     */
    submitImageForm: function() {
        $('.js-email-image-form').submit();
    },


    hideSettings: function() {
        $('.js-html-email-settings').hide();
    },

    showSettings: function() {
        $('.js-html-email-settings').show();
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
            type: 'POST',
            url: gdn.url('/dashboard/settings/removeemailimage'),
            data: {TransientKey: gdn.definition('TransientKey')},
            success: function() {
                $('.js-email-image').hide();
                $('.js-remove-email-image-button').hide();
            }
        });
    },

    /**
     * Opens a popup with a email preview showing the current color values in the color pickers.
     */
    emailPreview: function() {
        var textColor = $('#Form_Garden-dot-EmailTemplate-dot-TextColor').val();
        var backgroundColor = $('#Form_Garden-dot-EmailTemplate-dot-BackgroundColor').val();
        var containerBackgroundColor = $('#Form_Garden-dot-EmailTemplate-dot-ContainerBackgroundColor').val();
        var buttonTextColor = $('#Form_Garden-dot-EmailTemplate-dot-ButtonTextColor').val();
        var buttonBackgroundColor = $('#Form_Garden-dot-EmailTemplate-dot-ButtonBackgroundColor').val();

        $.ajax({
            type: 'POST',
            url: gdn.url('/dashboard/settings/emailpreview'),
            data: {
                TransientKey: gdn.definition('TransientKey'),
                textColor: textColor,
                backgroundColor: backgroundColor,
                containerBackgroundColor: containerBackgroundColor,
                buttonTextColor: buttonTextColor,
                buttonBackgroundColor: buttonBackgroundColor
            },
            success: function(data) {
                var w = window.open('','','width=800, height=900');
                $(w.document.body).html(data);
            }
        });
    }
}
