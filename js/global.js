// This file contains javascript that is global to the entire Garden application
jQuery(document).ready(function($) {

   // Main Menu dropdowns
   $('#Menu').menu({
      showDelay: 0,
      hideDelay: 0
   });
   
   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   $('a.Popup').popup();

   // This turns any form into a "post-in-place" form so it is ajaxed to save
   // without a refresh. The form must be within an element with the "AjaxForm"
   // class.
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
   var RedirectUrl = $('.RedirectUrl').text();
   if (RedirectUrl != '')
      setTimeout("document.location = '"+RedirectUrl+"';", 2000);

   // Make tables sortable if the tableDnD plugin is present.
   if ($.tableDnD)
      $("table.Sortable").tableDnD({onDrop: function(table, row) {
         var tableId = $($.tableDnD.currentTable).attr('id');
         // Add in the transient key for postback authentication
         var transientKey = $(':hidden[name$=TransientKey]');
         var data = $.tableDnD.serialize() + '&TableID=' + tableId + '&' + $(transientKey).attr('name') + '=' + $(transientKey).val();
         var webRoot = $('#Definitions #WebRoot').text();
         $.post(webRoot + "/garden/utility/sort/", data, function(response) {
            if (response == 'TRUE')
               $('#'+tableId+' tbody tr td').effect("highlight", {}, 1000);

         });
      }});

   // Format email addresses
   $('span.Email').livequery(function() {
      var html = $(this).html();
      var email = this;
      $(email).find('em').replaceWith('.');
      $(email).find('strong').replaceWith('@');
      email = $(email).text();
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

   definition = function(definition, defaultVal) {
      if (defaultVal == null)
         defaultVal = definition;
         
      var def = $('#Definitions #' + definition).text();
      if (def == '')
         def = defaultVal;
         
      return def;
   }
   inform = function(message) {
      if (message && message != null && message != '') {
         $('div.Messages').remove();
         $('<div class="Messages Information"><ul><li>' + message + '</li></ul></div>').appendTo('body').show();
      }
   }

   // Fill the search input with "search" if empty and blurred
   var searchText = definition('Search', 'Search');
   $('#Search input.InputBox').val(searchText);
   $('#Search input.InputBox').blur(function() {
      var searchText = definition('Search', 'Search');
      if ($(this).val() == '')
         $(this).val(searchText);
   });
   $('#Search input.InputBox').focus(function() {
      var searchText = definition('Search', 'Search');
      if ($(this).val() == searchText)
         $(this).val('');      
   });

});