jQuery(document).ready(function($) {

    $('div.add-new-tag a,a.TagName').popup({
        onUnload: function() {
            $('#Content').load(gdn.url('/dashboard/settings/tagging?DeliveryType=VIEW'));
        }
    });

    // Confirm deletes before performing them
//   $('a.Delete').popup({
//      confirm: true,
//      followConfirm: false,
//      afterConfirm: function(json, sender) {
//         $('#Content:first').load(gdn.url('/dashboard/settings/tagging?DeliveryType=VIEW'));
//      }
//   });

});
