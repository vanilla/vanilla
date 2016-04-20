// This file contains javascript that is specific to the /profile controller.
jQuery(document).ready(function($) {

    $('a.AddMessage, a.EditMessage').popup({
        onUnload: function(settings) {
            $('#Content').load(gdn.url('/message?DeliveryType=VIEW'));
        }
    });

    // Confirm deletes before performing them
    $('a.DeleteMessage').popup({
        confirm: true,
        followConfirm: false,
        afterConfirm: function(json, sender) {
            $('#Content').load(gdn.url('/message?DeliveryType=VIEW'));
        }
    });

});
