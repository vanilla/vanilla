jQuery(document).ready(function($) {
    var currentAction = null;

    // Gets the selected IDs as an array.
    var getIDs = function() {
        var IDs = [];
        $('input:checked').each(function(index, element) {
            if ($(element).attr('id') == 'SelectAll')
                return;

            IDs.push($(element).val());
        });
        return IDs;
    };

    var afterSuccess = function(data) {
        // Figure out the IDs that are currently in the view.
        var rows = [];
        $('#Log tbody tr').each(function(index, element) {
            if ($(element).attr('id') == 'SelectAll')
                return;
            rows.push($(element).attr('id'));
        });
        var rowsSelector = '#' + rows.join(',#');

        // Requery the view and put it in the table.
        $.get(
            window.location.href,
            {'DeliveryType': 'VIEW'},
            function(data) {
                $('#LogTable').html(data);
                setExpander();

                // Highlight the rows that are different.
                var $foo = $('#Log tbody tr').not(rowsSelector);

                $foo.effect('highlight', {}, 'slow');
            });

        // Update the counts in the sidepanel.
        $('.Popin').popin();
    };

    // Restores the
    var restore = function() {
        handleAction('/dashboard/log/restore');
    };

    var deleteForever = function() {
        handleAction('/dashboard/log/delete');
    };

    var deleteSpam = function() {
        handleAction('/dashboard/log/deletespam');
    };

    var setExpander = function() {
        $Expander = $('.Expander');
        $('.Expander').expander({
            slicePoint: 200,
            expandText: gdn.definition('ExpandText'),
            userCollapseText: gdn.definition('CollapseText')
        });
    };
    setExpander();

    $(document).delegate('.CheckboxCell', 'click', function(e) {
        var $checkbox = $('input:checkbox', this);
        $checkbox.trigger('click', true);
    });

    $(document).delegate('tbody .CheckboxCell input', 'click', function(e, flip) {
        e.stopPropagation();
        var $checkbox = $(this);

        var selected = $checkbox.prop('checked');
        if (flip)
            selected = !selected;

        if (selected)
            $checkbox.closest('tr').addClass('Selected');
        else
            $checkbox.closest('tr').removeClass('Selected');
    });

    $(document).on('click', '#SelectAll', function(e, flip) {
        e.stopPropagation();
        var selected = $(this).prop('checked');

        if (flip)
            selected = !selected;

        var table = $(this).closest('table').find('tbody');
        $('input:checkbox', table).prop('checked', selected);
        if (selected)
            $('tr', table).addClass('Selected');
        else
            $('tr', table).removeClass('Selected');
    });

    $('.RestoreButton').click(function(e) {
        var IDs = getIDs().join(',');
        currentAction = restore;

        // Popup the confirm.
        var bar = $.popup({afterSuccess: afterSuccess},
            function(settings) {
                $.get(
                    gdn.url('/dashboard/log/confirm/restore?logids=' + IDs),
                    {'DeliveryType': 'VIEW'},
                    function(data) {
                        $.popup.reveal(settings, data);
                    })
            });

        return false;
    });

    $('.DeleteButton').click(function(e) {
        var IDs = getIDs().join(',');
        currentAction = deleteForever;

        // Popup the confirm.
        var bar = $.popup({afterSuccess: afterSuccess}, function(settings) {
            $.get(
                gdn.url('/dashboard/log/confirm/delete?logids=' + IDs),
                {'DeliveryType': 'VIEW'},
                function(data) {
                    $.popup.reveal(settings, data);
                })
        });

        return false;
    });

    $('.SpamButton').click(function(e) {
        var IDs = getIDs().join(',');
        currentAction = deleteSpam;

        // Popup the confirm.
        var bar = $.popup({afterSuccess: afterSuccess}, function(settings) {
            $.get(
                gdn.url('/dashboard/log/confirm/deletespam?logids=' + IDs),
                {'DeliveryType': 'VIEW'},
                function(data) {
                    $.popup.reveal(settings, data);
                })
        });

        return false;
    });

    $('.NotSpamButton').click(function(e) {
        var IDs = getIDs().join(',');
        currentAction = restore;

        // Popup the confirm.
        var bar = $.popup({afterSuccess: afterSuccess}, function(settings) {
            $.get(
                gdn.url('/dashboard/log/confirm/notspam?logids=' + IDs),
                {'DeliveryType': 'VIEW'},
                function(data) {
                    $.popup.reveal(settings, data);
                })
        });

        return false;
    });

    $(document).on('click', '.ConfirmNo', function() {
        $.popup.close({});
        return false;
    });

    $(document).delegate('#Confirm_SelectAll', 'click', function() {
        var checked = $('#Confirm_SelectAll').prop('checked');
        $('#ConfirmForm input:checkbox').prop('checked', checked);
    });

    // Filter menu
    $('.FilterButton').click(function(e) {
        // Get selected value
        var category = $('#Form_CategoryID').val();

        // Ajax us to filtered results
        $('#LogTable').load(gdn.url('/dashboard/log/moderation?DeliveryType=VIEW&CategoryID=' + category));

        return false;
    });
});
