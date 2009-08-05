jQuery(document).ready(function($) {
   
/* Autosave functionality for comment & discussion drafts */
   $.fn.autosave = function(opts) {
      var options = $.extend({interval: 60000, button: false}, opts);
      var textarea = this;
      if (!options.button)
         return false;
      
      return this.each(function() {
         var autosaveOn = false;
         $(this).keydown(function() {
            if (autosaveOn == false) {
               autosaveOn = true;
               $(textarea).next().animate({opacity: 1.0}, options.interval, function() {
                  if ($(textarea).val() != '')
                     $(options.button).click();
                     
                  autosaveOn = false;
               });
            }
         });
      });
   }
});