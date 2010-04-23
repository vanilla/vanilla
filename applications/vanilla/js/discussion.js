jQuery(document).ready(function($) {
   
/* Options */

   // Show options (if present) when hovering over the comment.
   $('.Comment').livequery(function() {
      $(this).hover(function() {
         $(this).find('ul.Options:first').show();
      }, function() {
         $(this).find('ul.Options:first').hide();
         /*
         var opts = $(this).find('ul.Options:first');
         if (!$(opts).find('li.Parent').hasClass('Active'))
            $(opts).hide();
         */
      });
   });
   
/* Comment Form */

   if ($.autogrow)
      $('textarea.TextBox').livequery(function() {
         $(this).autogrow();
      });

   // Hijack the "Cancel" button on the comment form
   var cancelButton = $('a.Cancel');
   var draftId = $('#Form_DraftID').val();
   if (draftId == '')
      cancelButton.hide();
      
   // Reveal it if they start typing a comment
   $('div.CommentForm textarea').keydown(function() {
      $('a.Cancel:hidden').show();
   });
   
   // Reveal the textarea and hide previews.
   $('a.WriteButton, a.Cancel').livequery('click', function() {
      resetCommentForm();
      if ($(this).hasClass('Cancel'))
         clearCommentForm(this);
         
      return false;
   });
   
   // Hijack comment form button clicks
   $('div.CommentForm :submit, a.PreviewButton, a.DraftButton').livequery('click', function() {
      var btn = this;
      var parent = $(btn).parents('div.CommentForm');
      var frm = $(parent).find('form');
      var textbox = $(frm).find('textarea');
      var inpCommentID = $(frm).find('input:hidden[name$=CommentID]');
      var inpDraftID = $(frm).find('input:hidden[name$=DraftID]');
      var type = 'Post';
      var preview = $(btn).hasClass('PreviewButton');
      if (preview) {
         type = 'Preview';
         // If there is already a preview showing, kill processing.
         if ($('div.Preview').length > 0 || jQuery.trim($(textbox).val()) == '')
            return false;
      }
      var draft = $(btn).hasClass('DraftButton');
      if (draft) {
         type = 'Draft';
         // Don't save draft if string is empty
         if (jQuery.trim($(textbox).val()) == '')
            return false;
      }

      // Post the form, and append the results to #Discussion, and erase the textbox
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      postValues += '&Type='+type;
      var discussionID = $(frm).find('[name$=DiscussionID]');
      var prefix = discussionID.attr('name').replace('DiscussionID', '');
      var discussionID = discussionID.val();
      // Get the last comment id on the page
      var comments = $('ul.Discussion li.Comment');
      var lastComment = $(comments).get(comments.length-1);
      var lastCommentID = $(lastComment).attr('id').replace('Comment_', '');
      postValues += '&' + prefix + 'LastCommentID=' + lastCommentID;
      var action = $(frm).attr('action') + '/' + discussionID;
      $(frm).find(':submit').attr('disabled', 'disabled');            
      $(parent).find('div.Tabs ul:first').after('<span class="TinyProgress">&nbsp;</span>');
      // Also add a spinner for comments being edited
      $(btn).parents('div.Comment').find('div.Meta span:last').after('<span class="TinyProgress">&nbsp;</span>');
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old popups
            $('.Popup,.Overlay').remove();
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            // Remove any old popups if not saving as a draft
            if (!draft && json.FormSaved == true)
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
               $(parent).find('li.Active').removeClass('Active');
               $(btn).parents('li').addClass('Active');
               $(frm).find('textarea').after(json.Data);
               $(frm).find('textarea').hide();
               
            } else if (!draft) {
               // Clean up the form
               resetCommentForm();
               clearCommentForm($('a.Cancel'));

               // If editing an existing comment, replace the appropriate row
               var existingCommentRow = $('#Comment_' + commentID);
               if (existingCommentRow.length > 0) {
                  existingCommentRow.after(json.Data).remove();
                  $('#Comment_' + commentID).effect("highlight", {}, "slow");
               } else {   
                  gdn.definition('LastCommentID', commentID, true);
                  // If adding a new comment, show all new comments since the page last loaded, including the new one.
                  $(json.Data).appendTo('ul.Discussion')
                     .effect("highlight", {}, "slow");
               }
               
               // Remove any "More" pager links
               $('#PagerMore').remove();
               
               // Let listeners know that the comment was added.
               $(this).trigger('CommentAdded');
               
               // And scroll to them
               /*
                  var target = $('ul.Discussion #Comment_' + json.CommentID);
                  if (target.offset())
                     $('html,body').animate({scrollTop: target.offset().top}, 'fast');
               */

            }
            gdn.inform(json.StatusMessage);
         },
         complete: function(XMLHttpRequest, textStatus) {
            // Remove any spinners, and re-enable buttons.
            $('span.TinyProgress').remove();
            $(frm).find(':submit').removeAttr("disabled");
         }
      });
      return false;
   });
   
   function resetCommentForm() {
      var parent = $('div.CommentForm');
      $(parent).find('li.Active').removeClass('Active');
      $('a.WriteButton').parents('li').addClass('Active');
      $(parent).find('div.Preview').remove();
      $(parent).find('textarea').show();
      $('span.TinyProgress').remove();
   }

   // Utility function to clear out the comment form
   function clearCommentForm(cancelButton) {
      if (cancelButton != null)
         $(cancelButton).hide();
      
      $('.Popup,.Overlay').remove();
      var frm = $('div.CommentForm');
      frm.find('textarea').val('');
      frm.find('input:hidden[name$=CommentID]').val('');
      // Erase any drafts
      var draftInp = frm.find('input:hidden[name$=DraftID]');
      if (draftInp.val() != '')
         $.ajax({
            type: "POST",
            url: gdn.combinePaths(gdn.definition('WebRoot'), 'index.php/vanilla/drafts/delete/' + draftInp.val() + '/' + gdn.definition('TransientKey')),
            data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
            dataType: 'json'
         });         
         
      draftInp.val('');
      frm.find('div.Errors').remove();
      $('div.Information').fadeOut('fast', function() { $(this).remove(); });
      $(frm).trigger('clearCommentForm');
   }
   
   // Set up paging
   if ($.morepager)
      $('.MorePager').morepager({
         pageContainerSelector: 'ul.Discussion',
         afterPageLoaded: function() { $(this).trigger('CommentPagingComplete'); }
      });
      
   // Autosave comments
   $('a.DraftButton').livequery(function() {
      var btn = this;
      $('div.CommentForm textarea').autosave({ button: btn });
   });


/* Options */

   // Edit comment
   $('a.EditComment').livequery('click', function() {
      var btn = this;
      var parent = $(btn).parents('div.Comment');
      var msg = $(parent).find('div.Message');
      $(parent).find('div.Meta span:last').after('<span class="TinyProgress">&nbsp;</span>');
      if ($(msg).is(':visible')) {
         $.ajax({
            type: "POST",
            url: $(btn).attr('href'),
            data: 'DeliveryType=VIEW&DeliveryMethod=JSON',
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Remove any old popups
               $('.Popup,.Overlay').remove();
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
               $(msg).after(json.Data);
               $(msg).hide();
               $(parent).find('span.TinyProgress').remove();
            }
         });
      } else {
         $(parent).find('div.CommentForm').remove();
         $(parent).find('span.TinyProgress').remove();
         $(msg).show();
      }
      return false;
   });
   // Reveal the original message when cancelling an in-place edit.
   $('ul.Discussion div.Comment a.Cancel').livequery('click', function() {
      var btn = this;
      $(btn).parents('div.Comment').find('div.Message').show();
      $(btn).parents('div.CommentForm').remove();
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
   
   getNewTimeout = function() {   
      if(autoRefresh <= 0)
         return;
   
      setTimeout(function() {
         discussionID = gdn.definition('DiscussionID', 0);
         lastCommentID = gdn.definition('LastCommentID', 0);
         if(lastCommentID <= 0)
            return;
         
         $.ajax({
            type: "POST",
            url: gdn.combinePaths(gdn.definition('WebRoot', ''), 'index.php/discussion/getnew/' + discussionID + '/' + lastCommentID),
            data: "DeliveryType=ASSET&DeliveryMethod=JSON",
            dataType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {               
               if(json.Data && json.LastCommentID) {
                  gdn.definition('LastCommentID', json.LastCommentID, true);
                  $current = $("#Discussion").contents();
                  $(json.Data).appendTo("#Discussion")
                     .effect("highlight", {}, "slow");
               }
               gdn.processTargets(json.Targets);
               
               getNewTimeout();
            }
         });
      }, autoRefresh);
   }
   
   // Load new comments like a chat.
   autoRefresh = gdn.definition('Vanilla_Comments_AutoRefresh', 10) * 1000;
   getNewTimeout();
});