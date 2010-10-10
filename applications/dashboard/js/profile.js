// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   
   // Load tab content on tab-click
   $('.Tabs li a').click(function() {
      $('.Tabs li').removeAttr('class');
      $(this).parent('li').attr('class', 'Active');
      var tabs = $('div.Tabs');
      tabs.nextAll().remove();
      tabs.after('<div class="Loading">&nbsp;</div>');
      $.post(this.href, {'DeliveryType': 'VIEW'}, function(data) {
         tabs.nextAll().remove();
         tabs.after(data);
      });
      return false;
   });
   
   // Hijack "clear status" link clicks
   $('#Status a').live('click', function() {
      // hijack the request and clear out the status
      jQuery.get($(this).attr('href') + '?DeliveryType=BOOL');
      $('#Status').remove();      
      return false;      
   });

   // Set the max chars in the about form.
   $('form.About textarea').setMaxChars(1000);
   
   // Popup the picture form when the link is clicked
   $('li.PictureLink a').popup({hijackForms: false, afterLoad: function() {
      $('.Popup :submit').hide();
      $('.Popup :input').change(function() {
         $('.Popup form').submit();
         $('.Popup .Body').html('<div class="Loading">&nbsp;</div>');
      });
   }});

   // Ajax invitation uninvites and send agains if they're in a popup
   $('div.Popup a.Uninvite, div.Popup a.SendAgain').live('click', function() {
      var btn = this;
      var popupId = $('div.Popup').attr('id');
      $.ajax({
         type: "GET",
         url: $(btn).attr('href'),
         data: { 'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON' },
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            json = $.postParseJson(json);
            $.popup.reveal({ popupId: popupId }, json);
         }
      });

      return false;
   });

   // Thumbnail Cropper
   // Popup the picture form when the link is clicked
   $('li.ThumbnailLink a').popup({hijackForms: false, afterLoad: function() {
      $('#cropbox').Jcrop({
         onChange: setPreviewAndCoords,
         onSelect: setPreviewAndCoords,
         aspectRatio: 1
      });
      
      $('.Popup :submit').click(function() {
         $('.Popup .Body').children().hide().end().append('<div class="Loading">&nbsp;</div>');
      });
   }});
   
   $('li.Popup a').popup();

   $('#cropbox').Jcrop({
      onChange: setPreviewAndCoords,
      onSelect: setPreviewAndCoords,
      aspectRatio: 1
   });

   function setPreviewAndCoords(c) {
      var thumbSize = $('#Form_ThumbSize').val();
      var sourceHeight = $('#Form_HeightSource').val();
      var sourceWidth = $('#Form_WidthSource').val();
      var rx = thumbSize / c.w;
      var ry = thumbSize / c.h;
      $('#Form_x').val(c.x);
      $('#Form_y').val(c.y);
      $('#Form_w').val(c.w);
      $('#Form_h').val(c.h);
      $('#preview').css({
         width: Math.round(rx * sourceWidth) + 'px',
         height: Math.round(ry * sourceHeight) + 'px',
         marginLeft: '-' + Math.round(rx * c.x) + 'px',
         marginTop: '-' + Math.round(ry * c.y) + 'px'
      });
   }
   
   // Remove Profile Picture
   $('a.RemovePictureLink').popup({
      confirm: true,
      followConfirm: false
   });
});