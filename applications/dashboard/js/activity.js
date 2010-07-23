jQuery(document).ready(function($) {
   
   // Set the max chars in the activity comment boxes
   $('form.Activity textarea').setMaxChars(1000);
   
   // Hide activity deletes and hijack their clicks to confirm
   $('ul.Activities a.Delete, ul.Activities a.DeleteComment').popup({
      confirm: true,
      followConfirm: false,
      afterConfirm: function(json, sender) {
      var row = $(sender).parents('li:first');
         $(row).slideUp('fast', function() {
            $(row).remove();
         });
      }
   });
   
   // Reveal activity deletes on hover
   $('ul.Activities li').livequery(function() {
      $(this).find('a.Delete').hide();
      $(this).hover(function() {
         $(this).find('a.Delete').show();
      }, function() {
         $(this).find('a.Delete').hide();
      });
   });

/* Comments */

   // Hide/reveal the comments when the comment link is clicked
   $('a.CommentOption').live('click', function() {
      var comments = $(this).parents('li.Activity').find('ul.ActivityComments');
      comments.toggle();
      comments.find('a.CommentLink').click();
      return false;
   });
   
   // Hijack commentlink clicks
   $('a.CommentLink').live('click', function() {
      // Hide the anchor
      var anchor = this;
      $(anchor).hide();
      var row = $(anchor).parents('li.CommentForm');
   
      // Reveal the form
      var frm = $(row).find('form');
      frm.show();
      
      // Focus on the textbox 
      var textbox = frm.find('textarea');
      textbox.focus().blur(function() {
         // Hide the form onblur if empty
         if (this.value == '') {
            var comments = $(anchor).parents('.Comments');
            var children = $(comments).children();
            var rowCount = children.length - 1; // Subtract the commentform row
            if (rowCount > 0) {
               // Hide/clear the form and reveal the anchor
               $(comments).find('.Errors').remove();
               $(frm).hide();
               $(anchor).show();
            } else {
               // Hide all elements in .Comments
               $(comments).hide();
            }
         }
      });
      return false;
   });
   
   // Hijack comment form button clicks
   $('ul.ActivityComments form input.Button').live('click', function() {
      var button = this;
      var frm = $(button).parents('form');
      var row = $(frm).parents('li.CommentForm');
      var textbox = $(row).find('textarea');
      
      // Post the form, place the results above the input, and erase the textbox
      var postValues = frm.serialize() + '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      var activityId = frm.find('[name$=ActivityID]').val();
      var action = frm.attr('action');
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            json = $.postParseJson(json);
            
            // Remove any old errors from the form
            $('div.Errors').remove();

            if (json.FormSaved == false) {
               if (json.StatusMessage != null && json.StatusMessage != '')
                  $(row).prepend(json.StatusMessage);
            } else {
               $(row).before(json.Data);         
               textbox.val('').blur();
               // Make sure that hidden items appear
               $('ul.ActivityComments li.Hidden').slideDown('fast');
            }
         }
      });
      return false;
   });

});