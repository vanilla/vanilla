// This file contains javascript that is specific to editing conditions
jQuery(document).ready(function($) {
    // Hide/reveal elements based on the condition type.
    $(document).on("change", ".ConditionEdit .CondType", function() {
        var selectedValue = $(this).val();
        var types = ['permission', 'request', 'role'];
        var $tr = $(this).parents('table.ConditionEdit tr');
        // Show/hide the appropriate elements.
        for (var i = 0; i < types.length; i++) {
            var $elements = $(".Cond_" + types[i], $tr);
            if (types[i] == selectedValue)
                $elements.show();
            else
                $elements.hide();
        }
    });

    $(document).on("click", ".ConditionEdit .DeleteCondition", function() {
        var $tr = $(this).parents('table.ConditionEdit tr');
        $tr.remove();

        return false;
    });

    // Handle adding conditions.
    $(".ConditionEdit .AddCondition").click(function() {
        // Grab the template row.
        var $editor = $(this).parents('div.ConditionEdit');
        var templateHtml = $('table.ConditionEdit tfoot', $editor).html();

        // Add the new row.
        var $tbody = $('table.ConditionEdit tbody', $editor);
        $tbody.append(templateHtml);

        return false;
    });
});
