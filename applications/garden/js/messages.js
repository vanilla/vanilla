// This file contains javascript that is specific to the garden/profile controller.
jQuery(document).ready(function($) {
   
   $('textarea.TextBox').autogrow();
   
   $('a.AddMessage, a.EditMessage').popup({
      onUnload: function(settings) {
         $('#Content').load(combinePaths(definition('WebRoot', ''), 'index.php/garden/message?DeliveryType=VIEW'));
      }   
   });
   
   // Confirm deletes before performing them
   $('a.DeleteMessage').popup({
      confirm: true,
      followConfirm: false,
      afterConfirm: function(json, sender) {
         $(sender).parents('tr').remove();
      }
   });

});