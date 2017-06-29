// This file contains javascript that is specific to the /profile controller.
jQuery(document).ready(function($) {

   // Hijack "add message" clicks and handle via ajax...
   $.fn.handleMessageForm = function() {
      this.click(function() {
         var button = this;
         $(button).attr('disabled', 'disabled');
         var frm = $(button).parents('form').get(0);
         var textbox = $(frm).find('textarea');
         // Post the form, and append the results to #Discussion, and erase the textbox
         var postValues = $(frm).serialize();
         postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
         postValues += '&'+button.name+'='+button.value;
         var prefix = textbox.attr('name').replace('Message', '');
         // Get the last message id on the page
         var messages = $('ul.Conversation li');
         var lastMessage = $(messages).get(messages.length - 1);
         var lastMessageID = $(lastMessage).attr('id');
         postValues += '&' + prefix + 'LastMessageID=' + lastMessageID;
         $(button).before('<span class="TinyProgress">&#160;</span>');
         $.ajax({
            type: "POST",
            url: $(frm).attr('action'),
            data: postValues,
            dataType: 'json',
            error: function(xhr) {
               gdn.informError(xhr);
            },
            success: function(json) {
               // Remove any old errors from the form
               $(frm).find('div.Errors').remove();

               if (json.ErrorMessages) {
                  $(frm).prepend(json.ErrorMessages);
                  json.ErrorMessages = null;
               }

               if (json.FormSaved) {
                  // Clean up the form
                  clearMessageForm();

                  // And show the new comments
                  $('ul.Conversation').appendTrigger(json.Data);

                  // Remove any "More" pager links
                  $('#PagerMore').remove();

                  // And scroll to them
                  var target = $('#' + json.MessageID);
                  if (target.offset()) {
                     $('html,body').animate({scrollTop: target.offset().top}, 'fast');
                  }

                  // Let listeners know that the message was added.
                  $(document).trigger('MessageAdded');
                  $(frm).triggerHandler('complete');

                  gdn.inform(json);
               }
            },
            complete: function(XMLHttpRequest, textStatus) {
               // Remove any spinners, and re-enable buttons.
               $('span.TinyProgress').remove();
               $(frm).find(':submit').removeAttr("disabled");
            }
         });
         return false;

      });
   }
   $('#Form_ConversationMessage :submit').handleMessageForm();

   // Utility function to clear out the message form
   function clearMessageForm() {
      $('div.Popup').remove();
      var frm = $('#Form_ConversationMessage');
      frm.find('textarea').val('');
      frm.trigger('clearCommentForm');
      frm.find('div.Errors').remove();
      $('div.Information').fadeOut('fast', function() { $(this).remove(); });
   }

   $.fn.userTokenInput = function() {
      $(this).each(function() {
         /// Author tag token input.
           var $author = $(this);

           var author = $author.val();
           if (author && author.length) {
               author = author.split(",");
               for (i = 0; i < author.length; i++) {
                   author[i] = { id: i, name: author[i] };
               }
           } else {
               author = [];
           }

           // gdn.definition can't return null default because that'd be too easy
           var maxRecipients = gdn.definition('MaxRecipients', null);
           if (maxRecipients == 'MaxRecipients') {
               maxRecipients = null;
           }

           $author.tokenInput(gdn.url('/user/tagsearch'), {
               hintText: gdn.definition("TagHint", "Start to type..."),
               tokenValue: 'name',
               tokenLimit: maxRecipients,
               searchingText: '', // search text gives flickery ux, don't like
               searchDelay: 300,
               minChars: 1,
               zindex: 9999,
               prePopulate: author,
               animateDropdown: false
           });
      });
   };

   // Enable multicomplete on selected inputs
   $('.MultiComplete').userTokenInput();

   // Hack: When tokenLimit is reached, hintText will not go away after input is clicked
   // Force it to go away when we click the Body textarea
   $('#Form_Body').click(function() {
      $('.token-input-dropdown').css('display', 'none');
   });

   $('#Form_AddPeople :submit').click(function() {
      var btn = this;
      $(btn).hide();
      $(btn).before('<span class="TinyProgress">&#160;</span>');

      var frm = $(btn).parents('form');
      var textbox = $(frm).find('textarea');

      // Post the form, show the status and then redirect.
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: $(frm).serialize() + '&DeliveryType=VIEW&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(xhr, textStatus, errorThrown) {
            $('span.TinyProgress').remove();
            $(btn).show();
            gdn.informError(xhr);
         },
         success: function(json) {
            gdn.inform(json);
            if (json.RedirectTo)
              setTimeout(function() { window.location.replace(json.RedirectTo); }, 300);
         }
      });
      return false;
   });

   gdn.refreshConversation = function() {
       // Get the last ID.
       var conversationID = $('#Form_ConversationID').val();
       var lastID = $('.DataList.Conversation > li:last-child').attr('id');

       $.ajax({
           type: 'GET',
           url: gdn.url('/messages/getnew'),
           data: { conversationid: conversationID, lastmessageid: lastID, DeliveryType: 'VIEW' },
           success: function(html) {
               var $list = $('.DataList.Conversation');
               var $html = $('<ul>'+html+'</ul>');

               $('li.Item', $html).each(function(index) {
                   var id = $(this).attr('id');

                   if ($('#'+id).length == 0) {
                   $(this).appendTo($list).trigger('contentLoad');
                   }
               });
           }
       });
   }

   if (Vanilla.parent)
       Vanilla.parent.refreshConversation = gdn.refreshConversation;
});
