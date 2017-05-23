(function(window, $) {
    $(document)
    // Categories->Delete().
    // Hide/reveal the delete options when the DeleteDiscussions checkbox is un/checked.
        .on('change', '[name=ContentAction]', function () {
            if ($(this).val() === 'move') {
                $('[name=ReplacementCategoryID]').trigger('change');
                $('#ReplacementCategory').slideDown('fast');
                $('#DeleteCategory').slideUp('fast');
            } else {
                $('[name=ConfirmDelete]').trigger('change');
                $('#ReplacementCategory').slideUp('fast');
                $('#DeleteCategory').slideDown('fast');
            }
        })
        .on('change', '[name=ReplacementCategoryID]', function () {
            $('[name=Proceed]').prop('disabled', !$(this).val());
        })
        .on('change', '[name=ConfirmDelete]', function () {
            $('[name=Proceed]').prop('disabled', !$(this).prop('checked'));
        })
        // Categories->Delete()
        // Hide onload if unchecked.
        .on('contentLoad', function (e) {
            $('#ReplacementCategory, #DeleteCategory', e.target).hide();

            if ($('[name$=MoveContent]', e.target).is('checked')) {
                if ($('[name$=MoveContent]', e.target).val() === 'move') {
                    $('#ReplacementCategory').slideDown('fast');
                } else {
                    $('#DeleteCategory').slideDown('fast');
                }
            }

            $('#Form_Proceed').prop('disabled', true);
        })
    ;
})(window, jQuery);


jQuery(document).ready(function ($) {
    // Map plain text category to url code
    $("#Form_Name").keyup(function (event) {
        if ($('#Form_CodeIsDefined').val() == '0') {
            $('#UrlCode').show();
            var val = $(this).val();
            // A slug can't consist only of numbers.
            if (val.match(/^[0-9]+$/)) {
                val = '';
            } else {
                val = val.replace(/[ \/\\&.?;,<>'"]+/g, '-');
                val = val.replace(/\-+/g, '-').toLowerCase();
            }
            $("#Form_UrlCode").val(val);
            $("#UrlCode span").text(val);
        }
    });
    // Make sure not to override any values set by the user.
    $('#UrlCode span').text($('#UrlCode input').val());
    $("#Form_UrlCode").focus(function() {
        $('#Form_CodeIsDefined').val('1')
    });
    $('#UrlCode input, #UrlCode a.Save').hide();

    // Reveal input when "change" button is clicked
    $('#UrlCode a, #UrlCode span').click(function() {
        $('#UrlCode').find('input,span,a').toggle();
        $('#UrlCode span').text($('#UrlCode input').val());
        $('#UrlCode input').focus();
        return false;
    });

    // Set custom categories display.
    var displayCategoryPermissions = function() {
        var checked = $('#Form_CustomPermissions').prop('checked');
        if (checked) {
            $('.CategoryPermissions').show();
        } else {
            $('.CategoryPermissions').hide();
        }
        $('.panel-nav .js-fluid-fixed').trigger('reset.FluidFixed');
    };
    $('#Form_CustomPermissions').click(displayCategoryPermissions);
    displayCategoryPermissions();

    if ($.ui && $.ui.nestedSortable)
        $('ol.Sortable').nestedSortable({
            disableNesting: 'NoNesting',
            errorClass: 'SortableError',
            forcePlaceholderSize: true,
            handle: 'div',
            items: 'li',
            opacity: .6,
            placeholder: 'Placeholder',
            tabSize: 25,
            tolerance: 'pointer',
            toleranceElement: '> div',
            update: function() {
                $.post(
                    gdn.url('/vanilla/settings/sortcategories.json'),
                    {
                        'TreeArray': $('ol.Sortable').nestedSortable('toArray', {startDepthCount: 0}),
                        'TransientKey': gdn.definition('TransientKey')
                    },
                    function(response) {
                        if (!response || !response.Result) {
                            alert("Oops - Didn't save order properly");
                        }
                    }
                );
            }
        });
});
