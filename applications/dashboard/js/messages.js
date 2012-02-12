// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   
   $('textarea.TextBox').autogrow();
   
   $('a.AddMessage, a.EditMessage').popup({
      onUnload: function(settings) {
         $('#Content').load(gdn.url('/dashboard/message?DeliveryType=VIEW'));
      }   
   });
   
   // Confirm deletes before performing them
   $('a.DeleteMessage').popup({
      confirm: true,
      followConfirm: false,
      afterConfirm: function(json, sender) {
         $('#Content').load(gdn.url('/dashboard/message?DeliveryType=VIEW'));
      }
   });

});