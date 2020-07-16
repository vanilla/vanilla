$(document).on('contentLoad', function(e) {
    var element = e.target;
    $('#IsPointsAwardEnabled', element).change(function() {
        if ($(this).prop('checked')) {
            $('.js-point-awards-inputs', element).show().find('input').prop('disabled', false);
        } else {
            $('.js-point-awards-inputs', element).hide().find('input').prop('disabled', true);
        }
    }).change();
});
