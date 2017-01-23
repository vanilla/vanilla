// This file contains javascript that is specific to the /profile controller.
$(document).on('contentLoad', function(e) {

    var context = e.target;

    // Reveal password
    $(context).on('click', 'a.RevealPassword', function() {
        var inp = $(':password');
        var inp2 = $(inp).next();
        if ($(inp2).attr('id') != 'Form_ShowPassword') {
            $(inp).after('<input id="Form_ShowPassword" type="text" class="form-control" value="' + $(inp).val() + '" />');
            inp2 = $(inp).next();
            $(inp).change(function() {
                $(inp2).val($(inp).val());
            });
            $(inp2).change(function() {
                $(inp).val($(inp2).val());
            });
        }
        if ($(inp).is(":visible")) {
            $(this).html($(this).data('hideText'));
            $(inp).hide();
            $(inp2).show();
        } else {
            $(this).html($(this).data('showText'));
            $(inp).show();
            $(inp2).hide();
        }
        return false;
    });

    // Generate password
    $(context).on('click', 'a.GeneratePassword', function() {
        var passwd = gdn.generateString(7);
        $(':password').val(passwd);
        $('#Form_ShowPassword').val(passwd);
        return false;
    });

    // No password.
    var checkNoPassword = function() {
        var checked = $(this).prop('checked');

        if (checked) {
            $('.js-password').prop('disabled', true);
            $('.js-password-related').hide();
        } else {
            $('.js-password').prop('disabled', false);
            $('.js-password-related').show();
        }
    };

    $(context).on('click', '.js-nopassword input[type=checkbox]', checkNoPassword);

    checkNoPassword.apply($('.js-nopassword input[type=checkbox]'));

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
    $(context).on(newPasswordTriggers.join(' '), hideNewPassword);

    // When the password options are clicked, check to see if the admin/mod
    // wishes to set a new password for the user. If that's the case, show the
    // password reset input. Otherwise, hide it.
    $(context).on('change', '.PasswordOptions', function() {
        if ($("input:radio[name='ResetPassword']:checked").val() == 'Manual') {
            $('#NewPassword').slideDown('fast');
        } else {
            $('#NewPassword').slideUp('fast');
        }
    });
});
