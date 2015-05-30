// This file contains javascript that is specific to the garden/routes controller.
jQuery(document).ready(function($) {

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
