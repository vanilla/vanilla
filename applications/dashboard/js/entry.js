
// This file contains javascript that is specific to the /entry controller.
jQuery(document).ready(function($) {

    // Check to see if the selected email is valid
    $('#Register input[name$=Email], body.register input[name$=Email]').blur(function() {
        var email = $(this).val();
        if (email != '') {
            var checkUrl = gdn.url('/user/emailavailable');
            $.ajax({
                type: "GET",
                url: checkUrl,
                data: {email: email},
                dataType: 'text',
                error: function(xhr) {
                    gdn.informError(xhr, true);
                },
                success: function(text) {
                    if (text == 'FALSE')
                        $('#EmailUnavailable').show();
                    else
                        $('#EmailUnavailable').hide();
                }
            });
        }
    });

    // Check to see if the selected username is valid
    $('#Register input[name$=Name], body.register input[name$=Name]').blur(function() {
        var name = $(this).val();
        if (name != '') {
            var checkUrl = gdn.url('/user/usernameavailable/' + encodeURIComponent(name));
            $.ajax({
                type: "GET",
                url: checkUrl,
                dataType: 'text',
                error: function(xhr) {
                    gdn.informError(xhr, true);
                },
                success: function(text) {
                    if (text == 'FALSE')
                        $('#NameUnavailable').show();
                    else
                        $('#NameUnavailable').hide();
                }
            });
        }
    });

    var checkConnectName = function() {
        // If config setting AllowConnect is set to false, hide the password and return.
        if(!gdn.definition('AllowConnect', true)) {
            $('#ConnectPassword').hide();
            return;
        }
        if (gdn.definition('NoConnectName', false)) {
            $('#ConnectPassword').show();
            return;
        }
        var fineprint = $('#Form_ConnectName').siblings('.FinePrint');
        var selectedName = $('input[name$=UserSelect]:checked').val();
        if (!selectedName || selectedName == 'other') {
            var name = $('#Form_ConnectName').val();
            if (typeof(name) == 'string' && name != '') {
                var checkUrl = gdn.url('/user/usernameavailable/' + encodeURIComponent(name));
                $.ajax({
                    type: "GET",
                    url: checkUrl,
                    dataType: 'text',
                    error: function(xhr) {
                        gdn.informError(xhr, true)
                    },
                    success: function(text) {
                        if (text == 'TRUE') {
                            $('#ConnectPassword').hide();
                            if (fineprint.length) {
                                fineprint.html(gdn.definition('Choose a name to identify yourself on the site.'));
                            }
                        } else {
                            $('#ConnectPassword').show();
                            if (fineprint.length) {
                                fineprint.html(gdn.definition('Username already exists.'));
                            }
                        }
                    }
                });
            } else {
                $('#ConnectPassword').hide();
            }
        } else {
            $('#ConnectPassword').show();
        }
    }

    checkConnectName();
    $('#Form_ConnectName').keyup(checkConnectName);
    $('input[name$=UserSelect]').click(checkConnectName);

    // Check to see if passwords match
    $('input[name$=PasswordMatch]').blur(function() {
        var $pwmatch = $(this);
        var $pw = $pwmatch.closest('form').find('input[name=Password]');

        if ($pw.val() == $pwmatch.val())
            $('#PasswordsDontMatch').hide();
        else
            $('#PasswordsDontMatch').show();
    });

    $('#Form_ConnectName').focus(function() {
        $('input[value=other]').attr('checked', 'checked');
    });
});
