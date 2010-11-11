jQuery(document).ready(function($) {
   
/* Comment Form */

   if ($.autogrow)
      $('textarea.TextBox').livequery(function() {
         $(this).autogrow();
      });

   // Hijack the "Cancel" button on the comment form
   var cancelButton = $('a.Cancel');
      
   // Hide it if they leave the area without typing
   $('div.CommentForm textarea').blur(function(ev) {
      var Comment = $(ev.target).val();
      if (!Comment || Comment == '')
         $('a.Cancel').hide();
   });
   
   // Reveal the textarea and hide previews.
   $('a.WriteButton, a.Cancel').livequery('click', function() {
      if ($(this).hasClass('WriteButton')) {
         var frm = $(this).parents('.MessageForm').find('form');
         frm.trigger('WriteButtonClick', [frm]);
      }

      resetCommentForm(this);
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
      discussionID = discussionID.val();
      // Get the last comment id on the page
      var comments = $('ul.Discussion li.Comment');
      var lastComment = $(comments).get(comments.length-1);
      var lastCommentID = $(lastComment).attr('id');
      lastCommentID = lastCommentID.indexOf('Discussion_') == 0 ? 0 : lastCommentID.replace('Comment_', '');
      postValues += '&' + prefix + 'LastCommentID=' + lastCommentID;
      var action = $(frm).attr('action') + '/' + discussionID;
      $(frm).find(':submit').attr('disabled', 'disabled');
      $(parent).find('div.Tabs ul:first').after('<span class="TinyProgress">&nbsp;</span>');
      // Also add a spinner for comments being edited
      $(btn).parents('div.Comment').find('div.Meta span:last').after('<span class="TinyProgress">&nbsp;</span>');
      
      $(frm).triggerHandler('BeforeSubmit', [frm, btn]);
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old popups
            $('.Popup,.Overlay').remove();
            var msg;
            if (XMLHttpRequest.responseText)
               msg = XMLHttpRequest.responseText;
            else {
               msg = '<h1>Error</h1><p class="Wrap">';
               if(textStatus == 'timeout')
                  msg += 'Your request took too long to complete and timed out. Please try again.';
               else
                  msg += textStatus;
               msg += '</div>';
            }


            $.popup({}, msg);
         },
         success: function(json) {
            json = $.postParseJson(json);
            
            var processedTargets = false;
            // If there are targets, process them
            if (json.Targets && json.Targets.length > 0) {
               for(i = 0; i < json.Targets.length; i++) {
                  if (json.Targets[i].Type != "Ajax") {
                     json.Targets[i].Data = json.Data;
                     processedTargets = true;
                     break;
                   }
               }
               gdn.processTargets(json.Targets);
            }

            // If there is a redirect url, go to it
            if (json.RedirectUrl != null && jQuery.trim(json.RedirectUrl) != '') {
               resetCommentForm(btn);
               clearCommentForm(btn);
               window.location.replace(json.RedirectUrl);
               return false;
            }

            // Remove any old popups if not saving as a draft
            if (!draft && json.FormSaved == true)
               $('div.Popup,.Overlay').remove();

            var commentID = json.CommentID;
            
            // Assign the comment id to the form if it was defined
            if (commentID != null && commentID != '') {
               $(inpCommentID).val(commentID);
            }

            if (json.DraftID != null && json.DraftID != '')
               $(inpDraftID).val(json.DraftID);
               
            if (json.MyDrafts != null) {
               if (json.CountDrafts != null && json.CountDrafts > 0)
                  json.MyDrafts += '<span>'+json.CountDrafts+'</span>';
                  
               $('ul#Menu li.MyDrafts a').html(json.MyDrafts);
            }
            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();
            if (json.FormSaved == false) {
               $(frm).prepend(json.StatusMessage);
               json.StatusMessage = null;
            } else if (preview) {
               $(frm).trigger('PreviewLoaded', [frm]);
               $(parent).find('li.Active').removeClass('Active');
               $(btn).parents('li').addClass('Active');
               $(frm).find('textarea').after(json.Data);
               $(frm).find('textarea').hide();
               
            } else if (!draft) {
               // Clean up the form
               if (processedTargets)
                  btn = $('div.CommentForm :submit');

               resetCommentForm(btn);
               clearCommentForm(btn);

               // If editing an existing comment, replace the appropriate row
               var existingCommentRow = $('#Comment_' + commentID);
               if (processedTargets) {
                  // Don't do anything with the data b/c it's already been handled by processTargets
               } else if (existingCommentRow.length > 0) {
                  existingCommentRow.after(json.Data).remove();
                  $('#Comment_' + commentID).effect("highlight", {}, "slow");
               } else {
                  gdn.definition('LastCommentID', commentID, true);
                  // If adding a new comment, show all new comments since the page last loaded, including the new one.
                  $(json.Data).appendTo('ul.Discussion');
                  $('ul.Discussion li:last').effect("highlight", {}, "slow");
               }
               // Remove any "More" pager links
               $('#PagerMore').remove();

               // Let listeners know that the comment was added.
               $(document).trigger('CommentAdded');
               $(frm).triggerHandler('complete');
            }
            gdn.inform(json.StatusMessage);
            return false;
         },
         complete: function(XMLHttpRequest, textStatus) {
            // Remove any spinners, and re-enable buttons.
            $('span.TinyProgress').remove();
            $(frm).find(':submit').removeAttr("disabled");
         }
      });
      frm.triggerHandler('submit');
      return false;
   });
   
   function resetCommentForm(sender) {
      var parent = $(sender).parents('div.CommentForm');
      $(parent).find('li.Active').removeClass('Active');
      $('a.WriteButton').parents('li').addClass('Active');
      $(parent).find('div.Preview').remove();
      $(parent).find('textarea').show();
      $('span.TinyProgress').remove();
   }

   // Utility function to clear out the comment form
   function clearCommentForm(sender) {
      var container = $(sender).parents('li.Editing');
      $(container).removeClass('Editing');
      $('div.Popup,.Overlay').remove();
      var frm = $(sender).parents('div.CommentForm');
      frm.find('textarea').val('');
      frm.find('input:hidden[name$=CommentID]').val('');
      // Erase any drafts
      var draftInp = frm.find('input:hidden[name$=DraftID]');
      if (draftInp.val() != '')
         $.ajax({
            type: "POST",
            url: gdn.url('/vanilla/drafts/delete/' + draftInp.val() + '/' + gdn.definition('TransientKey')),
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
         afterPageLoaded: function() { $(document).trigger('CommentPagingComplete'); }
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
      var container = $(btn).parents('li.Comment');
      $(container).addClass('Editing');
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
               $('div.Popup,.Overlay').remove();
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
               json = $.postParseJson(json);
               
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
      
      $(document).trigger('CommentEditingComplete', [msg]);
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
            url: gdn.url('/discussion/getnew/' + discussionID + '/' + lastCommentID),
            data: "DeliveryType=ASSET&DeliveryMethod=JSON",
            dataType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
               json = $.postParseJson(json);
               
               if(json.Data && json.LastCommentID) {
                  gdn.definition('LastCommentID', json.LastCommentID, true);
                  $(json.Data).appendTo("ul.Discussion")
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