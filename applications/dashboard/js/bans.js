// This file contains javascript that is specific to the /routes controller.
jQuery(document).ready(function($) {

    // Pop add/edit route clicks and reload the page contents when finished.
    $('a.Add, a.Edit').popup({
        onUnload: function(settings) {
            $('#Content').load(window.location.href, {'DeliveryType': 'VIEW'});
        }
    });

    // Confirm deletes before performing them
    $('a.Delete').popup({
        confirm: true,
        followConfirm: false,
        doPost: true,
        afterConfirm: function(json, sender) {
            $(sender).parents('tr').remove();
        }
    });

});
