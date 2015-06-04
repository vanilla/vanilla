jQuery(document).ready(function($) {
    /* Autosave functionality for comment & discussion drafts */
    $.fn.autosave = function(opts) {
        var options = $.extend({interval: 60000, button: false}, opts);
        var textarea = this;
        if (!options.button)
            return false;

        var lastVal = null;

        var save = function() {
            var currentVal = $(textarea).val();
            if (currentVal != undefined && currentVal != '' && currentVal != lastVal) {
                lastVal = currentVal
                $(options.button).click();
            }
        };

        if (options.interval > 0) {
            setInterval(save, options.interval);
        }

        return this;
    }
});
