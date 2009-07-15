jQuery(document).ready(function($) {
   
/* Options */

   // Show options (if present) when hovering over the comment.
   $('.Comment').livequery(function() {
      $(this).hover(function() {
         $(this).find('ul.Options:first').show();
      }, function() {
         var opts = $(this).find('ul.Options:first');
         if (!$(opts).find('li.Parent').hasClass('Active'))
            $(opts).hide();
      });
   });
   
/* Comment Form */

   $('textarea.TextBox').livequery(function() {
      $(this).autogrow();
   });

   // Hijack the "Cancel" button on the comment form
   $('a.Cancel').hide().live('click', function() {
      clearCommentForm(this);
      return false;      
   });
   
   // Reveal it if they start typing a comment
   $('#CommentForm textarea').keydown(function() {
      $('a.Cancel:hidden').show();
   });
   
   // Hijack comment form button clicks
   $('#CommentForm :submit').livequery('click', function() {
   
      var btn = this;
      var frm = $(btn).parents('form').get(0);
      var textbox = $(frm).find('textarea');
      var inpCommentID = $(frm).find('input:hidden[name$=CommentID]');
      var inpDraftID = $(frm).find('input:hidden[name$=DraftID]');
      var preview = $(btn).attr('name') == $('#Form_Preview').attr('name') ? true : false;
      var draft = $(btn).attr('name') == $('#Form_SaveDraft').attr('name') ? true : false;
      
      // Post the form, and append the results to #Discussion, and erase the textbox
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      postValues += '&'+btn.name+'='+btn.value;
      var discussionID = $(frm).find('[name$=DiscussionID]');
      var prefix = discussionID.attr('name').replace('DiscussionID', '');
      var discussionID = discussionID.val();
      // Get the last comment id on the page
      var comments = $('#Discussion li.Comment');
      var lastComment = $(comments).get(comments.length-1);
      var lastCommentID = $(lastComment).attr('id').replace('Comment_', '');
      postValues += '&' + prefix + 'LastCommentID=' + lastCommentID;
      var action = $(frm).attr('action') + '/' + discussionID;
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old popups
            $('.Popup,.Overlay').remove();
            $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
         },
         success: function(json) {
            // Remove any old popups if not saving as a draft
            if (!draft)
               $('.Popup,.Overlay').remove();
               
            var commentID = json.CommentID;
            
            // Assign the comment id to the form if it was defined
            if (commentID != null && commentID != '') {
               $(inpCommentID).val(commentID);
            }

            if (json.DraftID != null && json.DraftID != '')
               $(inpDraftID).val(json.DraftID);
               
            if (json.MyDrafts != null)
               $('ul#Menu li.MyDrafts a').html(json.MyDrafts);

            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();

            if (json.FormSaved == false) {
               $(frm).prepend(json.StatusMessage);
               json.StatusMessage = null;
            } else if (preview) {
               // Pop up the new preview.
               $.popup({}, json.Data);
               
            } else if (!draft) {
               // Clean up the form
               clearCommentForm();                

               // If editing an existing comment, replace the appropriate row
               var existingCommentRow = $('#Comment_' + commentID);
               if (existingCommentRow.length > 0) {
                  existingCommentRow.after(json.Data).remove();
                  $('#Comment_' + commentID).effect("highlight", {}, "slow");
               } else {   
                  definition('LastCommentID', commentID, true);
                  // If adding a new comment, show all new comments since the page last loaded, including the new one.
                  $(json.Data).appendTo('#Discussion')
                     .effect("highlight", {}, "slow");
               }
               
               // Remove any "More" pager links
               $('#PagerMore').remove();
               
               // Let listeners know that the comment was added.
               $(this).trigger('CommentAdded');
               
               // And scroll to them
               var target = $('#Discussion #Comment_' + json.CommentID);
               if (target.offset())
                  $('html,body').animate({scrollTop: target.offset().top}, 'fast');

            }
            inform(json.StatusMessage);
         }
      });
      return false;
   });

   // Utility function to clear out the comment form
   function clearCommentForm(cancelButton) {
      if (cancelButton != null)
         $(cancelButton).hide();
         
      $('.Popup,.Overlay').remove();
      var frm = $('#CommentForm');
      frm.find('textarea').val('');
      frm.find('input:hidden[name$=CommentID]').val('');
      frm.find('input:hidden[name$=DraftID]').val('');
      frm.find('div.Errors').remove();
      $('div.Information').fadeOut('fast', function() { $(this).remove(); });
      $(frm).trigger('clearCommentForm');
   }
   
   // Set up paging
   if ($.morepager)
      $('.MorePager').morepager({
         pageContainerSelector: '#Discussion',
         afterPageLoaded: function() { $(this).trigger('CommentPagingComplete'); }
      });
      
   // Autosave comments
   $('#Form_SaveDraft').livequery(function() {
      var btn = this;
      $('#CommentForm textarea').autosave({ button: btn });
   });


/* Options */

   // Edit comment
   $('a.EditComment').popup({
      afterLoad: function() {
         $('.Popup .Button:last').hide();
      },
      afterSuccess: function(settings, response) {
         var btn = settings.sender;
         var row = $(btn).parents('li.Comment');
         $(row).after(response.Data);
         $(row).remove();
         // Let listeners know that the comment was edited.
         $(this).trigger('CommentEdited');
      }
   });

   // Delete comment
   $('a.DeleteComment').popup({
      confirm: true,
      followConfirm: false,
      deliveryType: 'BOOL', // DELIVERY_TYPE_BOOL
      afterConfirm: function(json, sender) {
         var row = $(sender).parents('li.Comment');
         if (json.ErrorMessage) {
            $.popup({}, json.ErrorMessage);
         } else {
            // Remove the affected row
            $(row).slideUp('fast', function() { $(this).remove(); });
         }
      }
   });
   
   $('a.Bookmark').click(function() {
      var btn = this;
      var oldClass = $(btn).attr('class');
      $(btn).addClass('Bookmarking');
      $.ajax({
         type: "POST",
         url: btn.href,
         data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Popup the error
            $(btn).attr('class', oldClass);
            $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
         },
         success: function(json) {
            // Otherwise just change the class & title on the anchor
            $(btn).attr('title', json.AnchorTitle);
            $(btn).attr('class', 'Bookmark');
            if (json.State == '1')
               $(btn).addClass('Bookmarked');
                  
            $('ul#Menu li.MyBookmarks a').html(json.MenuLink);
         }
      });
      return false;
   });   
   
   getNewTimeout = function() {   
      if(autoRefresh <= 0)
         return;
   
      setTimeout(function() {
         discussionID = definition('DiscussionID', 0);
         lastCommentID = definition('LastCommentID', 0);
         if(lastCommentID <= 0)
            return;
         
         $.ajax({
            type: "POST",
            url: definition('WebRoot', '') + '/discussion/getnew/' + discussionID + '/' + lastCommentID,
            data: "DeliveryType=ASSET&DeliveryMethod=JSON",
            dataType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
            },
            success: function(json) {               
               if(json.Data && json.LastCommentID) {
                  definition('LastCommentID', json.LastCommentID, true);
                  $current = $("#Discussion").contents();
                  $(json.Data).appendTo("#Discussion")
                     .effect("highlight", {}, "slow");
               }
               
               getNewTimeout();
            }
         });
      }, autoRefresh);
   }
   
   // Load new comments like a chat.
   autoRefresh = definition('Vanilla_Comments_AutoRefresh', 10) * 1000;
   getNewTimeout();
});