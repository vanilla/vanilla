// This file contains javascript that is specific to the /profile controller.
(function(window, $) {
    $(document)
        .on('click', '.js-new-avatar', function () {
            $(".js-new-avatar-upload", $(this).closest('form')).trigger("click");
        })
        .on('change', ".js-new-avatar-upload", function () {
            $(this).closest('.js-change-picture-form').submit();
        });
})(window, jQuery);

jQuery(document).ready(function($) {
    // Set the max chars in the about form.
    $('form.About textarea').setMaxChars(1000);

    // Ajax invitation uninvites and send agains if they're in a popup
    // Jan28, 2014 jQuery upgrade to 1.10.2, as live() removed in 1.7.
    // $('div.Popup a.Uninvite, div.Popup a.SendAgain').live('click', function() {
    $(document).on('click', 'div.Popup a.Uninvite, div.Popup a.SendAgain', function() {
        var btn = this;
        var popupId = $('div.Popup').attr('id');
        $.ajax({
            type: "GET",
            url: $(btn).attr('href'),
            data: {'DeliveryType': 'VIEW', 'DeliveryMethod': 'JSON'},
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
                $.popup.reveal({popupId: popupId}, json);
            }
        });

        return false;
    });

    // Handle heading clicks on preferences form
    $('table.PreferenceGroup thead .PrefCheckBox').each(function() {
        var cell = this;
        $(cell).css('cursor', 'pointer');
        cell.onclick = function() {
            var columnIndex = $(this)[0].cellIndex;
            var rows = $(this).parents('table').find('tbody tr');
            var checkbox = false;
            var state = -1;
            for (i = 0; i < rows.length; i++) {
                checkbox = $(rows[i]).find('td:eq(' + (columnIndex) + ') :checkbox');
                if ($(checkbox).is(':checkbox')) {
                    if (state == -1)
                        state = $(checkbox).prop('checked');

                    if (state) {
                        checkbox.prop('checked', false).trigger('change');
                    } else {
                        checkbox.prop('checked', true).trigger('change');
                    }
                }
            }
            return false;
        }
    });

    // Handle description clicks on preferences form
    $('table.PreferenceGroup tbody .Description, table.PreferenceGroup tbody .Depth_2').each(function() {
        var cell = this;
        var columnIndex = $(cell)[0].cellIndex;
        $(cell).css('cursor', 'pointer');
        cell.onclick = function() {
            var checkboxes = $(this).parents('tr').find('td.PrefCheckBox :checkbox');
            var state = false;
            for (i = 0; i < checkboxes.length; i++) {
                if (i == 0)
                    state = $(checkboxes[0]).prop('checked');

                if (state)
                    $(checkboxes[i]).prop('checked', false).trigger('change');
                else
                    $(checkboxes[i]).prop('checked', true).trigger('change');
            }
            return false;
        }
    });
});

$(document).on("contentLoad", function(e) {
    // Set Up Copy To Clipboard
    $('.js-copyToClipboard', e.target).each(function(){
        if (Clipboard && Clipboard.isSupported()) {
            $(this).show();
            var copyMessage = $(this).data('copymessage');
            var clipboard = new Clipboard(this, {
                target: function(trigger) {
                    gdn.informMessage(copyMessage);
                }
            });
        }
    });
});
