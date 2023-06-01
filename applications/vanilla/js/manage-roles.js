jQuery(document).ready(function ($) {
    $(".icon-remove").click(function () {
        $("#defaultType").slideUp();
    });
    // Set automatic role assignment display.
    var displayAutomaticRole = function () {
        var checked = $("#Form_EnableType").prop("checked");
        if (checked) {
            $(".roleType")[0].innerText = $(".roleType")[0].getAttribute("on");
            $(".AutomationType").show();
        } else {
            $(".roleType")[0].innerText = $(".roleType")[0].getAttribute("off");
            $(".AutomationType").hide();
        }
    };
    $("#Form_EnableType").click(displayAutomaticRole);
    if ($("#Form_EnableType").length > 0) {
        displayAutomaticRole();
    }
});
