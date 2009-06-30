// This file contains javascript that is specific to the garden/profile controller.
jQuery(document).ready(function($) {
   
   // Load tab content on tab-click
   $('ul.Tabs li a').click(function() {
      $('ul.Tabs li').removeAttr('class');
      $(this).parent('li').attr('class', 'Active');
      var tabs = $('ul.Tabs');
      tabs.nextAll().remove();
      tabs.after('<div class="Loading">&nbsp;</div>');
      $.post(this.href, {'DeliveryType': 'VIEW'}, function(data) {
         tabs.nextAll().remove();
         tabs.after(data);
      });
      return false;
   });
   
   // Hide the about form and replace it with text that is clickable
   $.fn.hideAboutForm = function() {
      // Hide the about form and replace it with a clickable about that reveals the form
      $(this).hide().hide(function() {
         var frm = this;
         var inp = $(frm).find('.InputBox');
         var about = $(inp).val();
         if (about == '')
            about = $('#Definitions #DefaultAbout').text();

         $(frm).before('<h4 class="AboutLink"><a href="#">'+ about +'</a></h4>');
         $(frm).prev('h4.AboutLink').click(function() {
            $('h4.AboutLink').remove();
            $(frm).show();
            $(inp).focus();
         });
      });
   }

   $('form.About').hideAboutForm();

   // Hijack about form submits
   $('form.About').submit(function() {
      var frm = this;
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=BOOL&DeliveryMethod=JSON';
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
         },
         success: function(json) {
            if (json['FormSaved'] == true) {
               $('form.About').hideAboutForm();
            }
         }
      });
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