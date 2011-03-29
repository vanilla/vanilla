jQuery(document).ready(function($) {
   
   // Show drafts delete button on hover
   $('li.Item').livequery(function() {
      var btn = $(this).find('a.Delete');
      $(btn).hide();
      $(this).hover(function() {
         $(btn).show();
      }, function() {
         $(btn).hide();
      });
   });

   // Set up paging
   if ($.morepager)
      $('.MorePager').livequery(function() {
         $(this).morepager({
            pageContainerSelector: 'ul.Discussions:last, ul.Drafts:last',
            afterPageLoaded: function() { $(document).trigger('DiscussionPagingComplete'); }
         });
      });

});
