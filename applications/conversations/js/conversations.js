// This file contains javascript that is specific to the garden/profile controller.
jQuery(document).ready(function($) {
   
   $('a.ClearConversation').popup({
      confirm: true,
      followConfirm: false
   });
   
   $('textarea.MessageBox, textarea.TextBox').livequery(function() {
      $(this).autogrow();
   });
   
   // Make the entire row clickable on the conversation list.
   $.fn.hoverRow = function() {
      return this.each(function() {
         var row = this;
         var anchor = $(row).find('a.Message');
         if (anchor.length > 0) {
            $(row).hover(function() {
               $(row).addClass('Active');
            }, function() {
               $(row).removeClass('Active');            
            }).click(function() {
               document.location = $(anchor).attr('href');
            });
         }
      });
   }   
   $('#Conversations li').hoverRow();
   
   // Hijack "add message" clicks and handle via ajax...
   $.fn.handleMessageForm = function() {
      this.click(function() {
         var button = this;
         var frm = $(button).parents('form').get(0);
         var textbox = $(frm).find('textarea');
         // Post the form, and append the results to #Discussion, and erase the textbox
         var postValues = $(frm).serialize();
         postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
         postValues += '&'+button.name+'='+button.value;
         var prefix = textbox.attr('name').replace('Message', '');
         // Get the last message id on the page
         var messages = $('#Conversation li');
         var lastMessage = $(messages).get(messages.length - 1);
         var lastMessageID = $(lastMessage).attr('id');
         postValues += '&' + prefix + 'LastMessageID=' + lastMessageID;
         $.ajax({
            type: "POST",
            url: $(frm).attr('action'),
            data: postValues,
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $('.Popup').remove();
               $.popup({}, definition('TransportError').replace('%s', textStatus));
            },
            success: function(json) {
               // Remove any old errors from the form
               $(frm).find('div.Errors').remove();

               if (json.StatusMessage) {
                  $(frm).prepend(json.StatusMessage);
                  json.StatusMessage = null;
               }
               if (json.FormSaved) {
                  // Clean up the form
                  clearMessageForm();                
   
                  // And show the new comments
                  $('#Conversation').append(json.Data);
                  
                  // Remove any "More" pager links
                  $('#PagerMore').remove();
                  
                  // And scroll to them
                  var target = $('#' + json.MessageID);
                  if (target.offset()) {
                     $('html,body').animate({scrollTop: target.offset().top}, 'fast');
                  }
                  inform(json.StatusMessage);
               }
            }
         });
         return false;
      
      });
   }
   $('#Form_ConversationMessage :submit').handleMessageForm();
   
   // Utility function to clear out the message form
   function clearMessageForm() {
      $('.Popup').remove();
      var frm = $('#Form_ConversationMessage');
      frm.find('textarea').val('');
      frm.find('div.Errors').remove();
      $('div.Information').fadeOut('fast', function() { $(this).remove(); });
   }
   
   // Enable multicomplete on selected inputs
   $('.MultiComplete').livequery(function() {
      $(this).autocomplete(
         combinePaths(definition('WebRoot'), 'index.php/garden/user/autocomplete/'),
         {
            minChars: 1,
            multiple: true,
            scrollHeight: 220,
            selectFirst: true
         }
      ).autogrow();
   });
   
   // Set up paging
   $('.MorePager').morepager({
      pageContainerSelector: '#Conversations, #Conversation'
   });
   
   $('#Form_AddPeople :submit').click(function() {
      var btn = this;
      $(btn).hide();
      $(btn).after('<span class="Progress">&nbsp;</span>');
      
      var frm = $(btn).parents('form');
      var textbox = $(frm).find('textarea');
      
      // Post the form, show the status and then redirect.
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: $(frm).serialize() + '&DeliveryType=VIEW&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $('span.Progress').remove();
            $(btn).show();
            $.popup({}, definition('TransportError').replace('%s', textStatus));
         },
         success: function(json) {
            inform(json.StatusMessage);
            if (json.RedirectUrl)
              setTimeout("document.location='" + json.RedirectUrl + "';", 300);
         }
      });
      return false;
   });

});