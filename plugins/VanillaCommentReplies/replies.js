jQuery(document).ready(function($) {
   
   // Show delete button when hovering over replies.
   $('.Reply').livequery(function() {
      $(this).hover(function() {
         $(this).find('a.DeleteReply').show();
      }, function() {
         $(this).find('a.DeleteReply').hide();
      });
   });

/* Replies */

   // Hide replies other than first and last
   $.fn.hideReplies = function() {
      return this.each(function() {
         var children = $(this).children();
         var rowCount = children.length - 1; // Subtract the replyform row
         // If there are more than 3 comments, hide the middle ones
         if (rowCount > 3) {
            // Don't bother if replies have already been hidden
            if ($(children[1]).attr('class') != 'Reply Reveal') {
               // Hide the middle comments
               for (i = 1; i < rowCount - 1; i++) {
                  $(children[i]).hide();
               }
   
               // Add a link to reveal hidden replies
               var text = (rowCount > 3) ? definition('Replies').replace('%s', rowCount - 2) : definition('Reply');
               $(children[0]).after('<li class="Reply Reveal"><a href="#">' + text + '</a></li>');
               
               // bind to the click event of the anchor and re-reveal the replies when it is clicked
               $(children[0]).next().find('a').click(function() {
                  $(this).parent().hide();
                  children.slideDown('fast');
                  return false;
               });
            }
         }
      });
   }   

   // Hide/reveal the replies when the comment reply link is clicked
   $('li.Comment ul.Info li.ReplyCount a').live('click', function() {
      var replies = $(this).parents('.Comment').find('.Replies');
      replies.toggle();
      replies.find('a.ReplyLink').click();
      return false;
   });
   
   // Hijack reply form link clicks
   $('ul.Replies a.ReplyLink').live('click', function() {
      // Hide the anchor
      var anchor = this;
      $(anchor).hide();
      var row = $(anchor).parent();
   
      // Reveal the form
      var frm = $(anchor).parent().find('form');
      frm.show();
      
      // Focus on the textbox 
      var textbox = frm.find('.TextBox');
      textbox.focus().blur(function() {
         // Hide the form onblur if empty
         if (this.value == '') {
            var replies = $(anchor).parents('.Replies');
            var children = $(replies).children();
            var rowCount = children.length - 1; // Subtract the replyform row
            $(replies).find('.Errors').remove();
            $(frm).hide();
            $(anchor).show();
         }
      });
      return false;
   });
   
   // Hijack reply form button clicks
   $('ul.Replies form input.Button').live('click', function() {
      var button = this;
      var frm = $(button).parents('form');
      var row = $(frm).parents('.ReplyForm');
      var textbox = $(frm).find('textarea');
      
      // Post the form and place the results above the input and erase the textbox
      var postValues = frm.serialize() + '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      // Get the most recent reply CommentID to be added
      var replyCommentID = frm.find('[name$=ReplyCommentID]');
      var prefix = replyCommentID.attr('name').replace('ReplyCommentID', '');
      replyCommentID = replyCommentID.val();
      var lastCommentID = frm.parent().parent().prev().attr('id');
      lastCommentID = lastCommentID == undefined ? replyCommentID : lastCommentID.replace('Comment_', '');
      postValues += '&' + prefix + 'LastCommentID=' + lastCommentID;
      var action = frm.attr('action') + '/' + replyCommentID;
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $.popup({}, definition('TransportError').replace('%s', textStatus));
         },
         success: function(json) {
            $('.ReplyForm .Errors').remove();
            if (json.FormSaved == false) {
               if (json.StatusMessage != null && json.StatusMessage != '') {
                  $(row).prepend(json.StatusMessage);
               }
            } else {
               definition('LastCommentID', json.CommentID, true);
               $(row).before(json.Data);
               $(row).parents('.Comment').find('ul.Info li.ReplyCount a').text(json.Replies);
               textbox.val('').blur();
            }
         }
      });
      return false;
   });
   
   function setVisibilities() {
      // Hide all reply forms and reveal all showform buttons
      $('ul.Replies a.ReplyLink').show();
      $('.ReplyForm form').hide();
   
      // Hide middle replies
      $('.Replies').hideReplies();
   }
   setVisibilities();
   
   // Bind the setvisibilities to a couple of different events
   $('body').bind('CommentAdded', setVisibilities);
   $('body').bind('CommentEdited', setVisibilities);
   $('body').bind('CommentPagingComplete', setVisibilities);

   // Delete reply
   $('a.DeleteReply').popup({
      confirm: true,
      followConfirm: false,
      deliveryType: 'BOOL', // DELIVERY_TYPE_BOOL
      afterConfirm: function(json, sender) {
         var row = $(sender).parents('li.Reply');
         if (json.ErrorMessage) {
            $.popup({}, json.ErrorMessage);
         } else {
            // Remove the affected row
            $(row).slideUp('fast', function() { $(this).remove(); });
         }
      }
   });

   
   $('a.DeleteReply').livequery(function() {
      $(this).hide();
   });


});