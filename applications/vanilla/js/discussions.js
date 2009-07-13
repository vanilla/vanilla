jQuery(document).ready(function($) {
   
   // Show drafts delete button on hover
   // Show options on each row (if present)
   $('.DiscussionRow').livequery(function() {
      var row = this;
      var opts = $(row).find('ul.Options');
      var btn = $(row).find('a.DeleteDraft');
      $(opts).hide();
      $(btn).hide();
      $(row).hover(function() {
         $(opts).show();
         $(btn).show();
         $(row).addClass('Active');
      }, function() {
         if (!$(opts).find('li.Parent').hasClass('Active'))
            $(opts).hide();
            
         $(btn).hide();
         $(row).removeClass('Active');            
      });
   });
   
   // Set up paging
   if ($.morepager)
      $('.MorePager').livequery(function() {
         $(this).morepager({
            pageContainerSelector: 'ul.Discussions:last'
         });
      });
   
   // Handle bookmark button clicks   
   $('a.Bookmark').live('click', function() {
      var btn = this;
      var parent = $(this).parents('.Bookmarks');
      var oldClass = $(btn).attr('class');
      $(btn).addClass('Bookmarking');
      $.ajax({
         type: "POST",
         url: btn.href,
         data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Popup the error
            $(btn).attr('class', oldClass);
            $.popup({}, $('#Definitions #TransportError').html().replace('%s', textStatus));
         },
         success: function(json) {
            // Remove this row if looking at a list of bookmarks
            // Is this the last item in the list?
            if ($(parent).children().length == 1) {
               // Remove the entire list
               $(parent).slideUp('fast', function() { $(this).remove(); });
               $(parent).prev().slideUp('fast', function() { $(this).remove(); });
            } else if ($(parent).length > 0) {
               // Remove the affected row
               $(btn).parents('.DiscussionRow').slideUp('fast', function() { $(this).remove(); });
            } else {
               // Otherwise just change the class & title on the anchor
               $(btn).attr('title', json.AnchorTitle);
               $(btn).attr('class', 'Bookmark');
               if (json.State == '1')
                  $(btn).addClass('Bookmarked');
                  
            }
            $('ul#Menu li.MyBookmarks a').html(json.MenuLink);
            // Add/remove the bookmark from the side menu.
            processTargets(json.Targets);
         }
      });
      return false;
   });   

});
