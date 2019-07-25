jQuery(document).ready(function($) {
    /* Autosave functionality for comment & discussion drafts */
    $.fn.autosave = function(opts) {
        var options = $.extend({interval: 60000, button: false}, opts);
        var textarea = this;
        if (!options.button)
            return false;

        var lastVal = $(textarea).val();

        var save = function() {
            var currentVal = $(textarea).val();
            var defaultValues = [
                undefined,
                null,
                '',
                '[{"insert":"\n"}]',
                lastVal
            ];
            if (!defaultValues.includes(currentVal)) {
                lastVal = currentVal;
                $(options.button).click();
            }
        };

        if (options.interval > 0) {
            setInterval(save, options.interval);
        }

        return this;
    }
});
