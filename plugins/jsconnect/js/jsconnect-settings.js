$(document).on('click', '.js-generate', function (e) {
    e.preventDefault();
    var $parent = $(this).closest('form');
    $.ajax({
        type: 'POST',
        url: gdn.url('/settings/jsconnect/addedit'),
        data: {
            DeliveryType: 'VIEW',
            DeliveryMethod: 'JSON',
            TransientKey: gdn.definition('TransientKey'),
            Generate: true
        },
        dataType: 'json',
        error: function (xhr) {
            gdn.informError(xhr);
        },
        success: function (json) {
            console.log(json);
            $('#Form_AuthenticationKey', $parent).val(json.AuthenticationKey);
            $('#Form_AssociationSecret', $parent).val(json.AssociationSecret);
            if ($('.modal-body').length) {
                $('.modal-body').scrollTop(0);
            } else {
                $(window).scrollTop(0);
            }
        }
    });
});

(function (window, $) {
    jQuery(window.document).ready(function ($) {
        var fragment = window.location.hash;
        if ($("form#jsConnect").length > 0) {
            $('form#jsConnect input[name$="fragment"]').val(fragment);
            $('form#jsConnect').submit();
        }
    });

    jQuery(window.document).on("contentLoad", function(e) {
        var context = e.target;

        if ($(context).is(".modal-dialog") === false) {
            return;
        }

        var updateControls = function() {
            var protocol = $("#Form_Protocol", context).val();

            var hashControl = $("#Form_HashType").parents(".form-group").first();
            if (protocol === "v2") {
                hashControl.show();
            } else {
                hashControl.hide();
            }
        }

        $("#Form_Protocol", context).change(updateControls);
        updateControls();
    });
})(window, jQuery);
