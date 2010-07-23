jQuery(document).ready(function($) {

   // Hijack import form button clicks
   $(':submit').live('click', function() {
      var btn = this;
      var frm = $(btn).parents('form').get(0);
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW DELIVERY_METHOD_JSON
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old popups
            $('.Popup').remove();
            $.popup({}, textStatus);
         },
         success: function(json) {
            json = $.postParseJson(json);
            
            // Remove any old errors from the form
            if (json.FormSaved == false) {
               $('#Content').html(json.Data);
            } else {
               CarryOn(json);
            }
         }
      });
      return false;
   });
   
   function CarryOn(json) {
      $('#Content').html(json.Data);
      if (json.NextUrl != null && json.NextUrl != 'Finished')
         $.ajax({
            type: "GET",
            url: json.NextUrl + '?DeliveryType=VIEW&DeliveryMethod=JSON',
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Remove any old popups
               $('.Popup').remove();
               $.popup({}, textStatus);
            },
            success: function(json) {
               json = $.postParseJson(json);
               
               CarryOn(json);
            }
         });
   }

});