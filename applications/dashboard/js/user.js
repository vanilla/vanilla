// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   
   // Reveal password
   $('a.RevealPassword').live('click', function() {
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
   $('a.GeneratePassword').live('click', function() {
      var passwd = gdn.generateString(7);
      $(':password').val(passwd);
      $('#Form_ShowPassword').val(passwd);
      return false;
   });
   
   // Hide/Reveal reset password input
   $('#NewPassword').livequery(function() {
      $(this).hide();
   });
   
   $('#Form_ResetPassword').live('click', function() {
      if ($(this).attr('checked'))
         $('#NewPassword').slideDown('fast');
      else
         $('#NewPassword').slideUp('fast');
   });
   
   // Make paging function in the user table
   $('.MorePager').morepager({
      pageContainerSelector: '#Users',
      pagerInContainer: true
   });
   
});