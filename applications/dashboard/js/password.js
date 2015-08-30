/**
 * Apply password strength meter to registration form
 */
jQuery(document).ready(function($) {

    $('input[type=password]').each(function(i, el) {
        el = $(el);
        if (!el.data('strength'))
            return;

        // Detect changes, set a timeout for calling the check
        var form = el.closest('form');
        if (!form.find('.PasswordStrength')) return;
        else {
            var pwFieldWidth = el.width();
            form.find('.PasswordStrength').css('width', pwFieldWidth);
        }
        var timeout = 0;

        el.on('keyup', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                checkPasswordStrength(el, form);
            }, 100);
        });

        if (el.val())
            checkPasswordStrength(el, form);
    });

    function checkPasswordStrength(el, form) {
        var username = form.find('input[name=Name]').val();
        if (!username)
            username = gdn.definition('Username', '');

        var pscore = gdn.password(el.val(), username);

        // update password strength
        var PasswordStrength = form.find('.PasswordStrength');
        if (PasswordStrength) {
            PasswordStrength.attr('class', 'PasswordStrength');
            var score = pscore.score;
            var passfail = pscore.pass ? 'Pass' : 'Fail';
            PasswordStrength.addClass('Score-' + score);
            PasswordStrength.addClass(passfail);

            var scoretext = pscore.reason;

            if (PasswordStrength.find('.StrengthText'))
                PasswordStrength.find('.StrengthText').html(scoretext);
        }
    }

});
