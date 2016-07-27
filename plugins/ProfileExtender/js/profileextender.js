jQuery(document).on('contentLoad', function(e) {
    SetProfileFormOptions();

    $("select[name='FormType']").change(function() {
        SetProfileFormOptions();
    });
});

function SetProfileFormOptions() {
    $("[name='Required']").prop('disabled', false);
    $("[name='OnRegister']").prop('disabled', false);
    $("[name='OnProfile']").prop('disabled', false);
    switch ($("select[name='FormType']").val()) {
        case 'Dropdown':
            $('.Label').slideDown('fast');
            $('.Options').slideDown('fast');
            $('.ShowOnProfiles').slideDown('fast');
            break;
        case 'DateOfBirth':
            $('.Label').slideUp('fast');
            $('.Options').slideUp('fast');
            $('.ShowOnProfiles').slideDown('fast');
            break;
        case 'CheckBox':
            $('.Label').slideDown('fast');
            $('.Options').slideUp('fast');
            $('.ShowOnProfiles').slideUp('fast');
            $("[name='OnProfile']").prop('checked', false);
            $("[name='OnProfile']").prop('disabled', true);

            if (!$("[name='Required']").prop('checked')) {
                $("[name='Required']").trigger('inputChecked');
                $("[name='Required']").prop('checked', true);
            }
            $("[name='Required']").trigger('inputDisabled');
            $("[name='Required']").prop('disabled', true);

            if (!$("[name='OnRegister']").prop('checked')) {
                $("[name='OnRegister']").trigger('inputChecked');
                $("[name='OnRegister']").prop('checked', true);
            }
            $("[name='OnRegister']").trigger('inputDisabled');
            $("[name='OnRegister']").prop('disabled', true);

            break;
        default:
            $('.Label').slideDown('fast');
            $('.Options').slideUp('fast');
            $('.ShowOnProfiles').slideDown('fast');
            break;
    }
}
