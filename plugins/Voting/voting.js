jQuery(document).ready(function($) {

   // Handle Vote button clicks
   $(document).on('click', 'div.VotesBox a', function() {
      var btn = this;
      var parent = $(this).parents('.Bookmarks');
      var oldClass = $(btn).attr('class');
      // $(btn).addClass('Bookmarking');
      $.ajax({
         type: "POST",
         url: btn.href,
         data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Popup the error
            $(btn).attr('class', oldClass);
            $.popup({}, definition('TransportError').replace('%s', textStatus));
         },
         success: function(json) {
            // Remove this row if looking at a list of bookmarks
            // Is this the last item in the list?
            if ($(parent).children().length == 1) {
               // Remove the entire list
               $(parent).slideUp('fast', function() { $(this).remove(); });
            } else if ($(parent).length > 0) {
               // Remove the affected row
               $(btn).parents('.DiscussionRow').slideUp('fast', function() { $(this).remove(); });
            } else {
               // Otherwise just change the class & title on the anchor
               $(btn).attr('title', json.AnchorTitle);

               // Change the Vote count
               // count = $(btn).html();
               // count = count.substr(count.lastIndexOf('>')+1);
               // count = json.FinalVote == '1' ? ++count : --count;
               txt = $(btn).find('span').text();
               $(btn).html('<span>' + txt + '</span>' + json.TotalScore);
               $(btn).blur();
            }
         }
      });
      return false;
   });

   // Handle follow button clicks
   $(document).on('click', 'div.FollowsBox a', function() {
      var btn = this;
      var parent = $(this).parents('.Bookmarks');
      var oldClass = $(btn).attr('class');
      // $(btn).addClass('Bookmarking');
      $.ajax({
         type: "POST",
         url: btn.href,
         data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Popup the error
            $(btn).attr('class', oldClass);
            $.popup({}, definition('TransportError').replace('%s', textStatus));
         },
         success: function(json) {
            // Remove this row if looking at a list of bookmarks
            // Is this the last item in the list?
            if ($(parent).children().length == 1) {
               // Remove the entire list
               $(parent).slideUp('fast', function() { $(this).remove(); });
            } else if ($(parent).length > 0) {
               // Remove the affected row
               $(btn).parents('li.Item').slideUp('fast', function() { $(this).remove(); });
            } else {
               // Otherwise just change the class & title on the anchor
               $(btn).attr('title', json.AnchorTitle);

               if ($(btn).hasClass('Bookmark')) {
                  $(btn).attr('class', 'Bookmark');
                  $(btn).attr('title', 'Follow');
                  if (json.State == '1') {
                     $(btn).attr('title', 'Unfollow');
                     $(btn).addClass('Bookmarked');
                  }
               } else {
                  // Change the Bookmark count
                  count = $(btn).html();
                  count = count.substr(count.lastIndexOf('>')+1);
                  count = json.State ? ++count : (count ? --count : 0);
                  txt = $(btn).find('span').text();
                  $(btn).html('<span>' + txt + '</span>' + count);
                  $(btn).blur();
               }
            }
            $('a.MyBookmarks span').text(json.CountBookmarks);
            // Add/remove the bookmark from the side menu.
            gdn.processTargets(json.Targets);
         }
      });
      return false;
   });

   // Handle Vote button clicks
   $(document).on('click', '.Voter a', function() {
      if (!$(this).hasClass('SignInPopup')) {
         var btn = this;
         var parent = $(this).parents('.Voter');
         var votes = $(parent).find('span');
         $.ajax({
            type: "POST",
            url: btn.href,
            data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $(btn).attr('class', oldClass);
               $.popup({}, definition('TransportError').replace('%s', textStatus));
            },
            success: function(json) {
               // Change the Vote count
               $(votes).text(json.TotalScore);
               gdn.inform(json);
            }
         });
         return false;
      }
   });

});
