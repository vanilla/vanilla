
/**
 * Apply password strength meter to registration form
 */
jQuery(document).ready(function($) {
   
   $('input[type=password]').each(function(i,el){
      el = $(el);
      if (el.attr('name').match(/(Match)/i))
         return;
      
      // Detect changes, set a timeout for calling the check
      var form = el.closest('form');
      var timeout = 0;
      
      el.on('keyup', function(e){
         clearTimeout(timeout);
         timeout = setTimeout(function(){ checkPasswordStrength(el, form); }, 100);
      });
      
      if (el.val())
         checkPasswordStrength(el, form);
   });
   
   function checkPasswordStrength(el, form) {
      var user = form.find('input[name=Name]');
      var pscore = gdn.password(el.val(), user.val());

      // update password strength
      var PasswordStrength = form.find('.PasswordStrength');
      if (PasswordStrength) {
         PasswordStrength.attr('class', 'PasswordStrength');
         var score = pscore.score;
         var passfail = pscore.pass ? 'Pass' : 'Fail';
         PasswordStrength.addClass('Score-'+score);
         PasswordStrength.addClass(passfail);

         var scoretext = '';
         switch (score) {
            case 0:
            case 1:
               scoretext = "Very weak";
               break;
            case 2:
               scoretext = "Weak";
               break;
            case 3:
               scoretext = "Ok";
               break;
            case 4:
               scoretext = "Good";
               break;
            case 5:
               scoretext = "Amazing";
               break;
         }

         if (PasswordStrength.find('.StrengthText'))
            PasswordStrength.find('.StrengthText').html(scoretext);
      }
   }
   
});