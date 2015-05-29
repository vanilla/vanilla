// This file is used by the dashboard/settings/registration controller/method
jQuery(document).ready(function($) {
    // Hide the unneeded form fields on page-load.
    var selected = $(':radio[checked]').val();
    // Show/Hide the invitation settings depending on the selected registration method
    if (selected == 'Invitation') {
        $('#InvitationSettings').show();
        $('#InvitationExpiration').show();
    } else {
        $('#InvitationSettings').hide();
        $('#InvitationExpiration').hide();
    }

    // Show/Hide the CaptchaSettings depending on the selected registration method
    if (selected == 'Captcha' || selected == 'Approval')
        $('#CaptchaSettings').show();
    else
        $('#CaptchaSettings').hide();

    // Show/Hide the NewUserRoles depending on the selected registration method
    if (selected == 'Closed')
        $('#NewUserRoles').hide();
    else
        $('#NewUserRoles').show();

    // Attach to all radio clicks on the page
    $(':radio').click(function() {
        // Show/Hide the invitation settings depending on the selected registration method
        if ($(this).val() == 'Invitation') {
            $('#InvitationSettings').slideDown('fast');
            $('#InvitationExpiration').slideDown('fast');
        } else {
            $('#InvitationSettings').slideUp('fast');
            $('#InvitationExpiration').slideUp('fast');
        }

        // Show/Hide the CaptchaSettings depending on the selected registration method
        if ($(this).val() == 'Captcha' || $(this).val() == 'Approval')
            $('#CaptchaSettings').slideDown('fast');
        else
            $('#CaptchaSettings').slideUp('fast');

        // Show/Hide the InvitationSettings depending on the selected registration method
        if ($(this).val() == 'Closed')
            $('#NewUserRoles').slideUp('fast');
        else
            $('#NewUserRoles').slideDown('fast');

    });
});
