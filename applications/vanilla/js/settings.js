jQuery(document).ready(function($) {

   // Settings->Index()
   // Hide/reveal "categories" from the dropdown when use categories is checked/unchecked
   $('#Form_Vanilla-dot-Categories-dot-Use').click(function() {
      $('[name=Configuration/Vanilla-dot-Discussions-dot-Home]').removeAttr('checked');
      if ($(this).attr('checked')) {
         $('[name=Configuration/Vanilla-dot-Discussions-dot-Home][value=categories]').parents('label').show();
         $('[name=Configuration/Vanilla-dot-Discussions-dot-Home]:last').attr('checked', 'checked');
      } else {
         $('[name=Configuration/Vanilla-dot-Discussions-dot-Home][value=categories]').parents('label').hide();
         $('[name=Configuration/Vanilla-dot-Discussions-dot-Home]:first').attr('checked', 'checked');
      }
   });
   // Hide onload if unchecked   
   if (!$('#Form_Vanilla-dot-Categories-dot-Use').attr('checked')) {
      $('[name=Configuration/Vanilla-dot-Discussions-dot-Home]').removeAttr('checked');
      $('[name=Configuration/Vanilla-dot-Discussions-dot-Home][value=categories]').parents('label').hide();
      $('[name=Configuration/Vanilla-dot-Discussions-dot-Home][value=categories]:first').attr('checked', 'checked');
   }

});