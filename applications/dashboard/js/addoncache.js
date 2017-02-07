$(document).ready(function(e) {
    var verifyCache = gdn.getMeta("VerifyCache");

    if (typeof verifyCache === "string") {
        $.ajax({
            url: gdn.url("addoncache/verify?Type=" + verifyCache + "&Target=" + window.location.pathname),
            success: function(data) {
                gdn.inform(data);
            }
        });
    }
});

$(document).on("click", "#ClearAddonCache", function(event) {
    // Ditch the inform message window this was in.
    $(event.target).parents(".InformWrapper").fadeOut("fast", function() {
        $(this).remove();
    });
});
