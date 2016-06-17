jQuery(document).ready(function($) {
    $(".js-new-avatar").click(function () {
        $(".js-new-avatar-upload").trigger("click");
        $(".js-new-avatar-upload").change(function() {
            $('form').submit();
        });
    });
});
