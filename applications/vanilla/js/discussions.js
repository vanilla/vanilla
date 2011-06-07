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

   /* Discussion Checkboxes */
   $('.DiscussionsTabs .AdminCheck :checkbox').click(function() {
      if ($(this).attr('checked'))
         $('.DataList .AdminCheck :checkbox').attr('checked', 'checked');
      else
         $('.DataList .AdminCheck :checkbox').removeAttr('checked');
   });
   $('.AdminCheck :checkbox').click(function() {
      // retrieve all checked ids
      var checkIDs = $('.DataList .AdminCheck :checkbox');
      var aCheckIDs = new Array();
      checkIDs.each(function() {
         checkID = $(this);
         aCheckIDs[aCheckIDs.length] = { 'checkId' : checkID.val() , 'checked' : checkID.attr('checked') };
      });
      $.ajax({
         type: "POST",
         url: gdn.url('/moderation/checkeddiscussions'),
         data: { 'CheckIDs' : aCheckIDs, 'DeliveryMethod' : 'JSON', 'TransientKey' : gdn.definition('TransientKey') },
         dataType: "json",
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            gdn.informMessage(XMLHttpRequest.responseText, { 'CssClass' : 'Dismissable' });
         },
         success: function(json) {
            gdn.inform(json);
         }
      });
   });

});
