jQuery(document).ready(function($) {
   // This turns any form into a "post-in-place" form so it is ajaxed to save
   // without a refresh. The form must be within an element with the "AjaxForm"
   // class.
   $.fn.handleAjaxForm = function(options) {
      var handle = this;
      $(this).find('form').each(function() {
         options = $.extend({
            frm:  this,
            data: { 'DeliveryType' : 'ASSET', 'DeliveryMethod' : 'JSON' },
            dataType: 'json',
            beforeSubmit: function(frm_data, frm) {
               options.frm = frm;
              // Hide the submit button & add a spinner
              // $('#' + frm.attr('id') + ' input.Button').attr('disabled', true);
              $(frm).find('input.Button').hide();
              $(frm).find('input.Button').after('<span class="Progress">&nbsp;</span>');
            },
            success: function(json, status, $frm) {
               if (json.FormSaved == true) {
                  inform(json.StatusMessage);
                  if (json.RedirectUrl) {
                     setTimeout("document.location='" + json.RedirectUrl + "';", 300);
                  } else if(json.DeliveryType == 'ASSET') {
                     $frm.parents('div:first').html(json.Data);
                  } else {
                     // Show the button again if not redirecting...
                     $(options.frm).find('input.Button').show();
                     $('span.Progress').hide();
                  }
               } else {
                  // Check to see if a target has been specified for the data.
                  if(json.Target) {
                     $(json.Target).html(json.Data);
                  } else if(json.DeliveryType == 'MESSAGE') {
                     inform(json.Data, false);
                     $frm.find('input.Button').show();
                     $frm.find('span.Progress').remove();
                  } else {
                     $('#' + options.frm.attr('id')).parents('div:first').html(json.Data);
                  }
               }
               // Re-attach the handler
               $($(handle).selector).handleAjaxForm();
             }
         }, options || {});
         
         $(this).ajaxForm(options);
      });
   }
});