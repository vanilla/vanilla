// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {

    // Reveal password
    $(document).on('click', 'a.RevealPassword', function() {
        var inp = $(':password');
        var inp2 = $(inp).next();
        if ($(inp2).attr('id') != 'Form_ShowPassword') {
            $(inp).after('<input id="Form_ShowPassword" type="text" class="InputBox" value="' + $(inp).val() + '" />');
            inp2 = $(inp).next();
            $(inp).hide();
            $(inp).change(function() {
                $(inp2).val($(inp).val());
            });
            $(inp2).change(function() {
                $(inp).val($(inp2).val());
            });
        } else {
            $(inp).toggle();
            $(inp2).toggle();
        }
        return false;
    });

    // Generate password
    $(document).on('click', 'a.GeneratePassword', function() {
        var passwd = gdn.generateString(7);
        $(':password').val(passwd);
        $('#Form_ShowPassword').val(passwd);
        return false;
    });

    // Hide/Reveal reset password input
    var hideNewPassword = function() {
            $('#NewPassword').hide();
        },
    // When any of these events are triggered, the "New Password" input will
    // be hidden
        newPasswordTriggers = [
            'popupReveal' // The user edit screen is loaded in a popup
        ];

    // Hide the password reset input on document ready
    hideNewPassword();

    // Hide the password reset input when any of the specified events are
    // triggered
    $(document).on(newPasswordTriggers.join(' '), hideNewPassword);

    // When the password options are clicked, check to see if the admin/mod
    // wishes to set a new password for the user. If that's the case, show the
    // password reset input. Otherwise, hide it.
    $(document).on('click', '.PasswordOptions', function() {
        if ($("input:radio[name='ResetPassword']:checked").val() == 'Manual') {
            $('#NewPassword').slideDown('fast');
        } else {
            $('#NewPassword').slideUp('fast');
        }
    });

    // Make paging function in the user table
//   $('.MorePager').morepager({
//      pageContainerSelector: '#Users',
//      pagerInContainer: true
//   });

});
