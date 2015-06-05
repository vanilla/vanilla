jQuery(document).ready(function($) {
    var refreshSteps = function() {
        // Have a query string?  Get ready to append.  No query string?  Setup the URL to receive one.
        var loc = window.location.href+(window.location.href.indexOf('?') > 0 ? '&' : '?');
        var url = loc+'DeliveryType=VIEW&DeliveryMethod=JSON';

        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            success: function(json) {
                json = $.postParseJson(json);

                // Refresh the view.
                $('#Content').html(json.Data);
                bindAjaxForm();

                // Go to the next step.
                if (!json.Complete && !json.Error) {
                    refreshSteps();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus == "timeout")
                    return;
                // Remove any old popups
                $('div.Popup').remove();
                // Add new popup with error
                $.popup({}, XMLHttpRequest.responseText);
            }
        });
    }

    var bindAjaxForm = function() {
        $('form').ajaxForm({
            dataType: 'json',
            success: function(json) {
                json = $.postParseJson(json);

                $('#Content').html(json.Data);
                bindAjaxForm();

                // Go to the next step.
                if (!json.Complete && !json.Error) {
                    refreshSteps();
                }
            }
        });
    };

    refreshSteps();
    bindAjaxForm();
});
