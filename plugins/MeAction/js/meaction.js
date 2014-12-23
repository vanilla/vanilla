jQuery(document).ready(function($) {
   $('.Comment').livequery(function() {
      $.each($('.MeActionName'), function(i, NameTag) {
         var MeNameText = $(NameTag).closest('.Comment, .Discussion').find('.Author a').text();
         $(NameTag).contents().replaceWith(MeNameText);
      });
   });
});