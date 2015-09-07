jQuery(document).ready(function($) {
    $(".js-new-avatar").click(function () {
        $(".js-new-avatar-upload").trigger("click");
        $(".js-new-avatar-upload").change(function() {
            $('form').submit();
        });
    });

    $(".js-less-link").click(function(e) {
        handleMore(e);
    });

    $(".js-more-link").click(function(e) {
        handleMore(e);
    });

    function handleMore(e) {
        e.preventDefault();
        $(".js-avatars-advanced-settings").toggle();
        $(".js-more-link").toggle();
        $(".js-less-link").toggle();
    }
});
