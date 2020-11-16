jQuery(document).on('contentLoad', function(e) {
    SetProfileFormOptions($(e.target));

    $("select[name='FormType']").change(function() {
        SetProfileFormOptions($('.js-profile-extender-form'));
    });
});

function SetProfileFormOptions($element) {
    $("[name='Required']", $element).prop('disabled', false);
    $("[name='OnRegister']", $element).prop('disabled', false);
    $("[name='OnProfile']", $element).prop('disabled', false);
    switch ($("select[name='FormType']", $element).val()) {
        case 'Dropdown':
            $('.js-label', $element).slideDown('fast');
            $('.js-options', $element).slideDown('fast');
            $('.js-show-on-profiles', $element).slideDown('fast');
            break;
        case 'DateOfBirth':
            $('.js-label', $element).slideUp('fast');
            $('.js-options', $element).slideUp('fast');
            $('.js-show-on-profiles', $element).slideDown('fast');
            break;
        case 'CheckBox':
            $('.js-label', $element).slideDown('fast');
            $('.js-options', $element).slideUp('fast');
            $('.js-show-on-profiles', $element).slideDown('fast');
            break;
        default:
            $('.js-label', $element).slideDown('fast');
            $('.js-options', $element).slideUp('fast');
            $('.js-show-on-profiles', $element).slideDown('fast');
            break;
    }
}
