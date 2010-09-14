// This file contains javascript that is specific to the dashboard/routes controller.
jQuery(document).ready(function($) {

   // Pop add/edit route clicks and reload the page contents when finished.
   $('a.AddRoute, a.EditRoute').popup({
      onUnload: function(settings) {
         $('#Content').load(gdn.url('/dashboard/routes?DeliveryType=VIEW'));
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