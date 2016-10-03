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
                // Refresh the view.
                $('#main-row .content').html(json.Data);
                bindAjaxForm();

                // Go to the next step.
                if (!json.Complete && !json.Error) {
                    refreshSteps();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus == "timeout") {
                    return;
                }
                gdn.informError(XMLHttpRequest.responseText);
            }
        });
    }

    var bindAjaxForm = function() {
        $('form').ajaxForm({
            dataType: 'json',
            success: function(json) {
                $('#main-row .content').html(json.Data);
                bindAjaxForm();

                // Go to the next step.
                if (!json.Complete && !json.Error) {
                    refreshSteps();
                }
            }
        });
    };

    if ($('.js-import-steps').length) {
        refreshSteps();
        bindAjaxForm();
    }
});

$(document).on('click', '#Form_ImportFile', function() {
    $('.js-new-path').trigger('inputChecked');
});
