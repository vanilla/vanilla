$(document).ready(function(e) {
    var verifyCache = gdn.getMeta("VerifyCache");

    if (typeof verifyCache === "string") {
        $.ajax({
            url: gdn.url("addoncache/verify?Type=" + verifyCache),
            success: function(data) {
                gdn.inform(data);
            }
        });
    }
});
