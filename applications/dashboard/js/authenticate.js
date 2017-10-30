$(function() {
    $('input[name=connectOption]').change(function() {
        $('.js-connectOption').hide();
        $('#'+this.value).show();
    });

    $('input[name=linkUserID]').change(function() {
        if (this.value === '-1') {
            $('#linkUserInfo').show();
        } else {
            $('#linkUserInfo').hide();
        }
    });
});
