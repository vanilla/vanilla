jQuery(document).ready(function($) {
    $(".js-new-avatar").click(function () {
        $(".js-new-avatar-upload").trigger("click");
        $(".js-new-avatar-upload").change(function() {
            $('form').submit();
        });
    });
    
    $(".js-save-avatar-crop").addClass('disabled');
    
    $(document).on('cropStart', function() {
        $(".js-save-avatar-crop").removeClass('disabled');
    });

    $(document).on('cropEnd', function() {
        $(".js-save-avatar-crop").addClass('disabled');
    });
});

