jQuery(document).ready(function($) {
   
   // Handle bookmark button clicks   
   $('a.Bookmark').live('click', function() {
      var btn = this;
      var parent = $(this).parents('.Bookmarks');
      $(btn).addClass('Bookmarking');
      $.ajax({
         type: "POST",
         url: btn.href,
         data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
         dataType: 'json',
         complete: function() {
            $(btn).removeClass('Bookmarking');
         },
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Popup the error.
            $.popup({}, XMLHttpRequest.responseText);
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
               if (json.State == '1')
                  $(btn).addClass('Bookmarked');
               else
                  $(btn).removeClass('Bookmarked');

               $('.CountBookmarks', $(btn)).text(json.CountDiscussionBookmarks);
                  
            }
            $('a.MyBookmarks').html(json.MenuText+' <span class="Count">'+json.CountBookmarks+'</span>');
            // Add/remove the bookmark from the side menu.
            gdn.processTargets(json.Targets);
         }
      });
      return false;
   });   

});
