// This file contains javascript that is specific to the garden/routes controller.
jQuery(document).ready(function($) {

   // Pop add/edit route clicks and reload the page contents when finished.
   $('a.AddRoute, a.EditRoute').popup({
      onUnload: function(settings) {
         $('#Content').load(combinePaths(definition('WebRoot', ''), 'index.php/garden/routes?DeliveryType=VIEW'));
      }   
   });
   
   // Confirm deletes before performing them
   $('a.DeleteRoute').popup({
      confirm: true,
      followConfirm: false,
      afterConfirm: function(json, sender) {
         $(sender).parents('tr').remove();
      }
   });

});