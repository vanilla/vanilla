// This file contains javascript that is specific to the dashboard/entry controller.
jQuery(document).ready(function($) {
   // Set up paging
   if ($.morepager)
      $('.MorePager').morepager({
         pageContainerSelector: 'ul.SearchResults'
      });

});