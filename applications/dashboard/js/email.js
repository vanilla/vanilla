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
        // Ajax call for preview popup
        $('.js-email-preview-button').click(emailStyles.emailPreview);
    },
    
    /**
     * Opens a popup with a email preview showing the current color values in the color pickers.
     */
    emailPreview: function() {
        var image = $('.js-image-preview').attr('src');
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
                image: image,
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
