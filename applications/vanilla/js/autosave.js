jQuery(document).ready(function($) {
/* Autosave functionality for comment & discussion drafts */
   $.fn.autosave = function(opts) {
      var options = $.extend({interval: 60000, button: false}, opts);
      var $textarea = $(this);

      if (!options.button || $textarea.length === 0) {
         return false;
      }
      
      var lastVal = null;
      var timerId = null;
      
      var save = function() {
         // Make sure the the text area is still attached to the dom.
         if ($textarea.closest('html').length === 0) {
            clearInterval(timerId);
            return;
         }

         var currentVal = $textarea.val();
         if (currentVal != undefined && currentVal != '' && currentVal != lastVal) {
            lastVal = currentVal
            $(options.button).click();
         }
      };
      
      if (options.interval > 0) {
         timerId = setInterval(save, options.interval);
      }
      
      return this;
   }
});
