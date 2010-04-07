
// This file contains javascript that is global to the entire Garden application
jQuery(document).ready(function($) {
   
   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=ClientHour]').livequery(function() {
      var d = new Date();
      $(this).val(d.getHours());
   });

   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetClientHour]').livequery(function() {
      var d = new Date();
      if (d.getHours() != $(this).val()) {
         $.post(
            combinePaths(definition('WebRoot', ''), 'index.php/utility/setclienthour/'+d.getHours()+'/'+definition('TransientKey')),
            'DeliveryType=BOOL'
         );
      }
   });
   
   // Hide/Reveal the "forgot your password" form if the ForgotPassword button is clicked.
   $('a.ForgotPassword').live('click', function() {
      $('#Form_User_Password').slideToggle('fast');
      return false;
   });
   
   if ($.fn.autogrow)
      $('textarea.Autogrow').livequery(function() {
         $(this).autogrow();
      });

   // Grab a definition from hidden inputs in the page
   definition = function(definition, defaultVal, set) {
      if (defaultVal == null)
         defaultVal = definition;
         
      var $def = $('#Definitions #' + definition);
      var def;
      
      if(set) {
         $def.val(defaultVal);
         def = defaultVal;
      } else {
         def = $def.val();
         if (typeof def == 'undefined' || def == '')
            def = defaultVal;
      }
         
      return def;
   }

   // Main Menu dropdowns
   if ($.fn.menu)
      $('#Menu').menu({
         showDelay: 0,
         hideDelay: 0
      });
      
   // Go to notifications if clicking on a user's notification count
   $('li.UserNotifications a span').click(function() {
      document.location = combinePaths(definition('UrlRoot', ''), '/profile/notifications');
      return false;
   });
   
   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   if ($.fn.popup)
      $('a.Popup').popup();
   
   // This turns any anchor with the "Popdown" class into an in-page pop-up, but
   // it does not hijack forms in the popup.
   if ($.fn.popup)
      $('a.Popdown').popup({hijackForms: false});
   
   // This turns SignInPopup anchors into in-page popups
   if ($.fn.popup)
      $('a.SignInPopup').popup({containerCssClass:'SignInPopup'});

   // Make sure that message dismissalls are ajax'd
   $('a.Dismiss').live('click', function() {
      var anchor = this;
      var container = $(anchor).parent();
      var transientKey = definition('TransientKey');
      var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;
      $.post($(anchor).attr('href'), data, function(response) {
         if (response == 'TRUE')
            $(container).slideUp('fast',function() {
               $(this).remove();
            });
      });
      return false;
   });

   // This turns any form into a "post-in-place" form so it is ajaxed to save
   // without a refresh. The form must be within an element with the "AjaxForm"
   // class.
   if ($.fn.handleAjaxForm)
      $('.AjaxForm').handleAjaxForm();
   
   // If a message group is clicked, hide it.
   $('div.Messages ul').live('click', function() {
      $(this).parents('div.Messages').fadeOut('fast', function() {
         $(this).remove();
      });
   });
   
   // If an information message appears on the screen, hide it after a few moments.
   $('div.Information').livequery(function() {
      setTimeout(function(){
         $('div.Information').fadeOut('fast', function() {
            $(this).remove();
         });
      }, 3000);
   });

   // If a page loads with a hidden redirect url, go there after a few moments.
   var RedirectUrl = definition('RedirectUrl', '');
   if (RedirectUrl != '')
      setTimeout("document.location = '"+RedirectUrl+"';", 2000);

   // Make tables sortable if the tableDnD plugin is present.
   if ($.tableDnD)
      $("table.Sortable").tableDnD({onDrop: function(table, row) {
         var tableId = $($.tableDnD.currentTable).attr('id');
         // Add in the transient key for postback authentication
         var transientKey = definition('TransientKey');
         var data = $.tableDnD.serialize() + '&DeliveryType=BOOL&TableID=' + tableId + '&TransientKey=' + transientKey;
         var webRoot = definition('WebRoot', '');
         $.post(combinePaths(webRoot, 'index.php/garden/utility/sort/'), data, function(response) {
            if (response == 'TRUE')
               $('#'+tableId+' tbody tr td').effect("highlight", {}, 1000);

         });
      }});

   // Format email addresses
   $('span.Email').livequery(function() {
      var html = $(this).html();
      var email = this;
      email = $(email).html().replace(/<em>dot<\/em>/g, '.').replace(/<strong>at<\/strong>/g, '@');
      $(this).html('<a href="mailto:' + email + '">' + email + '</a>');
   });

   // Make sure that the commentbox & aboutbox do not allow more than 1000 characters
   $.fn.setMaxChars = function(iMaxChars) {
      $(this).live('keyup', function() {
         var txt = $(this).val();
         if (txt.length > iMaxChars)
            $(this).val(txt.substr(0, iMaxChars));
      });
   }

   // Notify the user with a message
   inform = function(message, wrapInfo) {
      if(wrapInfo == undefined) {
         wrapInfo = true;
      }
      
      if (message && message != null && message != '') {
         $('div.Messages').remove();
         if(wrapInfo)
            $('<div class="Messages Information"><ul><li>' + message + '</li></ul></div>').appendTo('body').show();
         else
            $(message).appendTo('body').show();
      }
   }
   
   // Generate a random string of specified length
   generateString = function(length) {
      if (length == null)
         length = 5;
         
      var chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%*';
      var string = '';
      var pos = 0;
      for (var i = 0; i < length; i++) {
         pos = Math.floor(Math.random() * chars.length);
         string += chars.substring(pos, pos + 1);
      }
      return string;
   }
   
   // Combine two paths and make sure that there is only a single directory concatenator
   combinePaths = function(path1, path2) {
      if (path1.substr(-1, 1) == '/')
         path1 = path1.substr(0, path1.length - 1);
         
      if (path2.substring(0, 1) == '/')
         path2 = path2.substring(1);
      
      return path1 + '/' + path2;
   }

   processTargets = function(targets) {
      if(!targets || !targets.length)
         return;
      for(i = 0; i < targets.length; i++) {
         var item = targets[i];
         $target = $(item.Target);
         switch(item.Type) {
            case 'Append':
               $target.append(item.Data);
               break;
            case 'Before':
               $target.before(item.Data);
               break;
            case 'After':
               $target.after(item.Data);
               break;
            case 'Prepend':
               $target.prepend(item.Data);
               break;
            case 'Remove':
               $target.remove();
               break;
            case 'Text':
               $target.text(item.Data);
               break;
            case 'Html':
               $target.html(item.Data);
         }
      }
   }

   // Fill the search input with "search" if empty and blurred
   var searchText = definition('Search', 'Search');
   $('#Search input.InputBox').val(searchText);
   $('#Search input.InputBox').blur(function() {
      var searchText = definition('Search', 'Search');
      if (typeof $(this).val() == 'undefined' || $(this).val() == '')
         $(this).val(searchText);
   });
   $('#Search input.InputBox').focus(function() {
      var searchText = definition('Search', 'Search');
      if ($(this).val() == searchText)
         $(this).val('');      
   });
   
   // Add a spinner onclick of buttons with this class
   $('input.SpinOnClick').live('click', function() {
      $(this).after('<span class="AfterButtonLoading">&nbsp;</span>').removeClass('SpinOnClick');
   });
   
   // Confirmation for item removals
   $('a.RemoveItem').click(function() {
      if (!confirm('Are you sure you would like to remove this item?')) {
         return false;
      }
   });
   
});