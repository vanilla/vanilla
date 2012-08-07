// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   // Set the max chars in the about form.
   $('form.About textarea').setMaxChars(1000);
   
   // Popup the picture form when the link is clicked
   $('.ChangePicture,.AddPicture').popup({hijackForms: false, afterLoad: function() {
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

   if ($.Jcrop)
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

   // Handle heading clicks on preferences form
   $('table.PreferenceGroup thead .PrefCheckBox').each(function() {
      var cell = this;
      $(cell).css('cursor', 'pointer');
      cell.onclick = function() {
        var columnIndex = $(this)[0].cellIndex;
        var rows = $(this).parents('table').find('tbody tr');
        var checkbox = false;
        var state = -1;
        for (i = 0; i < rows.length; i++) {
          checkbox = $(rows[i]).find('td:eq(' + (columnIndex) + ') :checkbox');
          if ($(checkbox).is(':checkbox')) {
            if (state == -1)
               state = $(checkbox).attr('checked');
               
            if (state) {
              checkbox.removeAttr('checked');
            } else {
              checkbox.attr('checked', 'checked');
            }
          }
        }
        return false;
      }
   });

   // Handle description clicks on preferences form
   $('table.PreferenceGroup tbody .Description, table.PreferenceGroup tbody .Depth_2').each(function() {
      var cell = this;
      var columnIndex = $(cell)[0].cellIndex;
      $(cell).css('cursor', 'pointer');
      cell.onclick = function() {
         var checkboxes = $(this).parents('tr').find('td.PrefCheckBox :checkbox');
         var state = false;
         for (i = 0; i < checkboxes.length; i++) {
            if (i == 0)
               state = $(checkboxes[0]).attr('checked');
            
            if (state)
               $(checkboxes[i]).removeAttr('checked');
            else
               $(checkboxes[i]).attr('checked', 'checked');
         }
         return false;
      }
   });
});
