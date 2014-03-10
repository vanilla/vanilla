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

   if ($('.AdminCheck :checkbox').not(':checked').length == 1)
      $('.AdminCheck [name="Toggle"]').prop('checked', true).change();

   // Set up sorting
   $(document).undelegate('.SortDiscussions', 'click');
   $(document).delegate('.SortDiscussions', 'click', function() {
      var SendData = {
         'TransientKey': gdn.definition('TransientKey'),
         'DiscussionSort': $(this).attr('data-field')
      };
      jQuery.ajax({
         dataType: 'json',
         type: 'post',
         url: gdn.url('discussions/sort'),
         data: SendData,
         success: function(json) {
            location.reload();
         }
      });

      return false;
   });

   /* Discussion Checkboxes */
   $('.AdminCheck [name="Toggle"]').click(function() {
      if ($(this).prop('checked'))
         $('.DataList .AdminCheck :checkbox, tbody .AdminCheck :checkbox').prop('checked', true).change();
      else
         $('.DataList .AdminCheck :checkbox, tbody .AdminCheck :checkbox').prop('checked', false).change();
   });
   $('.AdminCheck :checkbox').click(function() {
      // retrieve all checked ids
      var checkIDs = $('.DataList .AdminCheck :checkbox, tbody .AdminCheck :checkbox');
      var aCheckIDs = new Array();
      checkIDs.each(function() {
         checkID = $(this);

         // jQuery 1.9 removed the old behaviour of checkID.attr('checked') when
         // checking for boolean checked value. It now returns undefined. The
         // correct method to check boolean checked is with checkID.prop('checked');
         // Vanilla would either return the string 'checked' or '', so make
         // sure same return values are generated.
         aCheckIDs[aCheckIDs.length] = {
            'checkId' : checkID.val(),
            'checked' : checkID.prop('checked') || '' // originally just, wrong: checkID.attr('checked')
         };
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
