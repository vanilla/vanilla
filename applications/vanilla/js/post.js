jQuery(document).ready(function($) {
   
   if ($.autogrow)
      $('textarea.TextBox').livequery(function() {
         $(this).autogrow();
      });
   
   // Hijack comment form button clicks
   $('#CommentForm :submit').click(function() {
      var btn = this;
      var frm = $(btn).parents('form').get(0);
      
      // Handler before submitting
      $(frm).triggerHandler('BeforeCommentSubmit', [frm, btn]);
      
      var textbox = $(frm).find('textarea');
      var inpCommentID = $(frm).find('input:hidden[name$=CommentID]');
      var inpDraftID = $(frm).find('input:hidden[name$=DraftID]');
      var preview = $(btn).attr('name') == $('#Form_Preview').attr('name') ? true : false;
      var draft = $(btn).attr('name') == $('#Form_SaveDraft').attr('name') ? true : false;
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      postValues += '&'+btn.name+'='+btn.value;
      var discussionID = $(frm).find('[name$=DiscussionID]').val();
      var action = $(frm).attr('action') + '/' + discussionID;
      $(frm).find(':submit:last').after('<span class="Progress">&nbsp;</span>');
      $(frm).find(':submit').attr('disabled', 'disabled');
      
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old popups
            $('.Popup').remove();
            // Add new popup with error
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            json = $.postParseJson(json);
            
            // Remove any old popups if not saving as a draft
            if (!draft)
               $('.Popup').remove();
            
            // Assign the comment id to the form if it was defined
            if (json.CommentID != null && json.CommentID != '') {
               $(inpCommentID).val(json.CommentID);
               gdn.definition('LastCommentID', json.CommentID, true);
            }
               
            if (json.DraftID != null && json.DraftID != '')
               $(inpDraftID).val(json.DraftID);
               
            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();

            if (json.FormSaved == false) {
               $(frm).prepend(json.StatusMessage);
               json.StatusMessage = null;
            } else if (preview) {
               // Pop up the new preview.
               $.popup({}, json.Data);
            } else if (!draft && json.DiscussionUrl != null) {
               $(frm).triggerHandler('complete');
               // Redirect to the discussion
               document.location = json.DiscussionUrl;
            }
            gdn.inform(json.StatusMessage);
         },
         complete: function(XMLHttpRequest, textStatus) {
            // Remove any spinners, and re-enable buttons.
            $('span.Progress').remove();
            $(frm).find(':submit').removeAttr("disabled");
         }
      });
      $(frm).triggerHandler('submit');
      return false;
   });
   
   // Hijack discussion form button clicks
   $('#DiscussionForm :submit').click(function() {
      var btn = this;
      var frm = $(btn).parents('form').get(0);
      
      // Handler before submitting
      $(frm).triggerHandler('BeforeDiscussionSubmit', [frm, btn]);
      
      var textbox = $(frm).find('textarea');
      var inpDiscussionID = $(frm).find(':hidden[name$=DiscussionID]');
      var inpDraftID = $(frm).find(':hidden[name$=DraftID]');
      var preview = $(btn).attr('name') == $('#Form_Preview').attr('name') ? true : false;
      var draft = $(btn).attr('name') == $('#Form_SaveDraft').attr('name') ? true : false;
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      postValues += '&'+btn.name+'='+btn.value;
      // Add a spinner and disable the buttons
      $(frm).find(':submit:last').after('<span class="Progress">&nbsp;</span>');
      $(frm).find(':submit').attr('disabled', 'disabled');      
      
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $('.Popup').remove();
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            json = $.postParseJson(json);
            
            // Remove any old popups if not saving as a draft
            if (!draft)
               $('.Popup').remove();

            // Assign the discussion id to the form if it was defined
            if (json.DiscussionID != null)
               $(inpDiscussionID).val(json.DiscussionID);
               
            if (json.DraftID != null)
               $(inpDraftID).val(json.DraftID);

            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();

            if (json.FormSaved == false) {
               $(frm).prepend(json.StatusMessage);
               json.StatusMessage = null;
            } else if (preview) {
               // Pop up the new preview.
               $.popup({}, json.Data);
               
            } else if (!draft) {
               $(frm).triggerHandler('complete');
               // Redirect to the new discussion
               document.location = json.RedirectUrl;
            }
            gdn.inform(json.StatusMessage);
         },
         complete: function(XMLHttpRequest, textStatus) {
            // Remove any spinners, and re-enable buttons.
            $('span.Progress').remove();
            $(frm).find(':submit').removeAttr("disabled");
         }
      });
      $(frm).triggerHandler('submit');
      return false;
   });
   
   // Autosave
   $('#Form_SaveDraft').livequery(function() {
      var btn = this;
      $('#CommentForm textarea').autosave({ button: btn });
      $('#DiscussionForm textarea').autosave({ button: btn });
   });
   
   
});