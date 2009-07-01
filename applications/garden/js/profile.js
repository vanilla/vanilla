// This file contains javascript that is specific to the garden/profile controller.
jQuery(document).ready(function($) {
   
   // Load tab content on tab-click
   $('ul.Tabs li a').click(function() {
      $('ul.Tabs li').removeAttr('class');
      $(this).parent('li').attr('class', 'Active');
      var tabs = $('ul.Tabs');
      tabs.nextAll().remove();
      tabs.after('<div class="Loading">&nbsp;</div>');
      $.post(this.href, {'DeliveryType': 3}, function(data) {
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

   // Hijack activity comment form submits
   $('form.Activity :submit').live('click', function() {
      var but = this;
      var frm = $(this).parent('form');
      var inp = $(frm).find('textarea');
      // Only submit the form if the textarea isn't empty
      if ($(inp).val() != '') {
         $('span.Progress').remove();
         $(but).after('<span class="Progress">&nbsp;</span>');
         var postValues = $(frm).serialize();
         postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON';
         $.ajax({
            type: "POST",
            url: $(frm).attr('action'),
            data: postValues,
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
            },
            success: function(json) {
               $('span.Progress').remove();
               if (json['FormSaved'] == true) {
                  $(inp).val('');
                  $('ul.Activities').prepend(json['Data']);
                  // Make sure that empty rows are removed
                  $('ul.Activities li.Empty').slideUp('fast');
                  // Make sure that hidden items appear
                  $('ul.Activities li.Hidden').slideDown('fast');
                  // If the user's status was updated, show it.
                  if (json['UserData'] != '') {
                     $('div.User').remove();
                     $('div.Profile').prepend(json['UserData']);
                  }
               }
            }
         });
      }
      return false;
   });
   
   // Set the max chars in the about form.
   $('form.About textarea').setMaxChars(1000);
   // Popup the picture form when the link is clicked
   $('li.PictureLink a').popup({hijackForms: false, afterLoad: function() {
      $('.Popup :input').change(function() {
         $('.Popup :input').click();
         $('.Popup .Content').empty();
         $('.Popup .Body').children().hide().end().append('<div class="Loading">&nbsp;</div>');
      });
      $('.Popup :submit').hide();
   }});
   
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