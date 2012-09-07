(function($) {
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
              // Add a spinner
              var btn = $(frm).find('input.Button:last');
              if ($(btn).parent().find('span.Progress').length == 0) {
                 $(btn).after('<span class="Progress">&#160;</span>');
              }
            },
            success: function(json, status, $frm) {
               json = $.postParseJson(json);
               
               if (json.FormSaved == true) {
                  gdn.inform(json);
                  if (json.RedirectUrl) {
                     setTimeout("document.location='" + json.RedirectUrl + "';", 300);
                  } else if(json.DeliveryType == 'ASSET') {
                     $frm.parents($(handle).selector).html(json.Data);
                  } else {
                     // Remove the spinner if not redirecting...
                     $('span.Progress').remove();
                  }
               } else {
                  // Check to see if a target has been specified for the data.
                  if(json.Target) {
                     $(json.Target).html(json.Data);
                  } else if(json.DeliveryType == 'MESSAGE') {
                     gdn.inform(json.Data, false);
                     $frm.find('span.Progress').remove();
                  } else {
                     $frm.parents($(handle).selector).html(json.Data);
                  }
               }
               // If there are additional targets in the result then set them now.
               if(json.Targets) {
                  for(var i = 0; i < json.Targets.length; i++) {
                     var item = json.Targets[i];
                     if(item.Type == 'Text') {
                        $(item.Target).text($.base64Decode(item.Data));
                     } else {
                        $(item.Target).html($.base64Decode(item.Data));
                     }
                  }
               }
               
               // Re-attach the handler
               $($(handle).selector).handleAjaxForm(options);
             }
         }, options || {});
         
         $(this).ajaxForm(options);
      });
   }
})(jQuery);
