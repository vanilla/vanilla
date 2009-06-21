// This file contains javascript that is specific to the garden/routes controller.
jQuery(document).ready(function($) {
   
   // Handle popping up setup screens when enabling applications
   $.fn.handleAppModify = function(options) {
      var frm = this;
      var btn = $(frm).find(':button');
      options = $.extend({
         frm:  frm,
         data: { 'DeliveryType' : 2, 'DeliveryMethod' : 2 }, // Make sure only the view/content is delivered.
         dataType: 'json',
         beforeSubmit: function(frm_data, frm) {
           // Hide the submit button & add a spinner
           $(btn).hide();
           $(btn).after('<span class="Progress">&nbsp;</span>');
         },
         success: function(json) {
            if (json.FormSaved == true) {
               inform(json.StatusMessage);
               if (json.RedirectUrl) {
                  setTimeout("document.location='" + json.RedirectUrl + "';", 300);
               } else {
                  // Show the button again if not redirecting...
                  $(btn).show();
                  $('span.Progress').hide();
               }
            } else if (json.Go) {
               $.ajax({
                  url: json.Go + '?DeliveryType=3&DeliveryMethod=2',
                  cache: false,
                  success: function(html){
                    $.popup({}, html);
                  }
                });

            } else {
               // Pop Up the form
               $.popup({}, json.Data);
            }
          }
      }, options || {});   
      frm.ajaxForm(options);
   }
   $('form').handleAppModify();
});