jQuery(document).ready(function($) {
   var currentAction = null;

   // Gets the selected IDs as an array.
   var getIDs = function() {
      var IDs = [];
      $('input:checked').each(function(index, element) {
         if ($(element).attr('id') == 'SelectAll')
            return;
         
         IDs.push($(element).val());
      });
      return IDs;
   };

   var handleAction = function(url) {
      var IDs = getIDs();

      $.ajax({
            type: "GET",
            url: gdn.url(url+'?logids='+IDs.join(',')),
            dataType: 'text',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(text) {
               // Figure out the IDs that are currently in the view.
               var rows = [];
               $('#Log tbody tr').each(function(index, element) {
                  if ($(element).attr('id') == 'SelectAll')
                     return;
                  rows.push($(element).attr('id'));
               });
               var rowsSelector = '#'+rows.join(',#');

               // Requery the view and put it in the table.
               $.get(
                  window.location.href,
                  {'DeliveryType': 'VIEW'},
                  function (data) {
                     $('#LogTable').html(data);
                     setExpander();

                     // Highlight the rows that are different.
                     var $foo = $('#Log tbody tr').not(rowsSelector);

                     $foo.effect('highlight', {}, 'slow');
                  });

               // Update the counts in the sidepanel.
               $('.Popin').popin();
            }
         });
   }

   // Restores the
   var restore = function() {
      handleAction('/dashboard/log/restore');
   };

   var deleteForever = function() {
      handleAction('/dashboard/log/delete');
   };

   var setExpander = function() {
      $Expander = $('.Expander');
      $('.Expander').expander({slicePoint: 200, expandText: gdn.definition('ExpandText'), userCollapseText: gdn.definition('CollapseText')});
   };
   setExpander();

   $('tbody .CheckboxCell input').live('click', function(e) {
      var selected = $(this).attr('checked');
      $(this).closest('tr').toggleClass('Selected', selected);
   });

   $('#SelectAll').live('click', function(e) {
      var selected = $(this).attr('checked');
      var table = $(this).closest('table');
      $('input:checkbox', table).attr('checked', selected);
      if (selected)
         $('tbody tr', table).addClass('Selected', selected);
      else
         $('tbody tr', table).removeClass('Selected', selected);
   });

   $('.RestoreButton').click(function(e) {
      var IDs = getIDs().join(',');
      currentAction = restore;

      // Popup the confirm.
      var bar = $.popup({}, function(settings) {
         $.get(
            gdn.url('/dashboard/log/confirm/restore?logids='+IDs),
            {'DeliveryType': 'VIEW'},
            function (data) {
               $.popup.reveal(settings, data);
            })
         });
      
      return false;
   });

   $('.DeleteButton').click(function(e) {
      var IDs = getIDs().join(',');
      currentAction = deleteForever;

      // Popup the confirm.
      var bar = $.popup({}, function(settings) {
         $.get(
            gdn.url('/dashboard/log/confirm/delete?logids='+IDs),
            {'DeliveryType': 'VIEW'},
            function (data) {
               $.popup.reveal(settings, data);
            })
         });

      return false;
   });

   $('.NotSpamButton').click(function(e) {
      var IDs = getIDs().join(',');
      currentAction = restore;

      // Popup the confirm.
      var bar = $.popup({}, function(settings) {
         $.get(
            gdn.url('/dashboard/log/confirm/notspam?logids='+IDs),
            {'DeliveryType': 'VIEW'},
            function (data) {
               $.popup.reveal(settings, data);
            })
         });

      return false;
   });

   $('.ConfirmNo').live('click', function() {
      $.popup.close({});
      return false;
   });

   $('.ConfirmYes').live('click', function() {
      if ($.isFunction(currentAction)) {
         currentAction.call();
      }

      $.popup.close({});
      return false;
   });
});