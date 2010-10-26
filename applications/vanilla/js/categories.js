jQuery(document).ready(function($) {

   if ($.fn.alphanumeric)
      $('#Form_UrlCode').alphanumeric({allow:"-"});

   // Map plain text category to url code
   $("#Form_Name").keyup(function(event) {
      if ($('#Form_CodeIsDefined').val() == '0') {
         $('#UrlCode').show();
         val = $(this).val().replace(/[ ]+/g, '-').replace(/[^a-z0-9\-]+/gi,'').toLowerCase();
         $("#Form_UrlCode").val(val);
         $("#UrlCode span").text(val);
      }
   });
   // Make sure not to override any values set by the user.
   $('#UrlCode span').text($('#UrlCode input').val());
   $("#Form_UrlCode").focus(function() {
      $('#Form_CodeIsDefined').val('1')
   });
   $('#UrlCode input, #UrlCode a.Save').hide();
   if ($('#UrlCode input').val() == '')
      $('#UrlCode').hide();
   
   // Reveal input when "change" button is clicked
   $('#UrlCode a, #UrlCode span').click(function() {
      $('#UrlCode').find('input,span,a').toggle();
      $('#UrlCode span').text($('#UrlCode input').val());
      $('#UrlCode input').focus();
      return false;
   });

   // Hide/reveal the permissions grids when the AllowDiscussions checkbox is un/checked.
   $('[name=Category/IsParent]').click(function() {
      if ($(this).attr('checked'))
         $('#Permissions,#UrlCode').slideUp('fast');
      else
         $('#Permissions,#UrlCode').slideDown('fast');
   });
   // Hide onload if unchecked   
   if ($('[name=Category/IsParent]').attr('checked'))
      $('#Permissions,#UrlCode').hide();
   
   // Categories->Delete()
   // Hide/reveal the delete options when the DeleteDiscussions checkbox is un/checked.
   $('[name=Form/DeleteDiscussions]').click(function() {
      if ($(this).attr('checked')) {
         $('#ReplacementCategory,#ReplacementWarning').slideDown('fast');
         $('#DeleteDiscussions').slideUp('fast');
      } else {
         $('#ReplacementCategory,#ReplacementWarning').slideUp('fast');
         $('#DeleteDiscussions').slideDown('fast');
      }
   });
   // Categories->Delete()
   // Hide onload if unchecked   
   if (!$('[name=Form/DeleteDiscussions]').attr('checked')) {
      $('#ReplacementCategory,#ReplacementWarning').hide();
      $('#DeleteDiscussions').show();
   } else {
      $('#ReplacementCategory,#ReplacementWarning').show();
      $('#DeleteDiscussions').hide();
   }

   // Categories->Manage()
   // Make category table sortable
   if ($.tableDnD) {
      saveAndReload = function(table, row) {
         var webRoot = gdn.definition('WebRoot', '');
         var transientKey = gdn.definition('TransientKey');
         var tableId = $($.tableDnD.currentTable).attr('id');
         var data = $.tableDnD.serialize() + '&TableID=' + tableId + '&DeliveryType=VIEW&Form/TransientKey=' + transientKey;
         $.post(gdn.url('/vanilla/settings/sortcategories/'), data, function(response) {
            if (response == 'TRUE') {
               // Reload the page content...
               $.get(gdn.url('/vanilla/settings/managecategories/?DeliveryType=VIEW'), function(data){
                  $('#Content').html(data);
                  $('table.Sortable tbody tr td').effect("highlight", {}, 1000);
                  $("table.Sortable").tableDnD({onDrop: saveAndReload});
               });
            }
         });
      }
      $("table.Sortable").tableDnD({onDrop: saveAndReload});
   }

});