jQuery(document).ready(function($) {
    /**
     * Trying to refactor this functionality to save drafts as a user types is tricky.
     *
     * The textarea value is updated programmatically which does *not* trigger any
     * change events. Further, the value attribute is updated (only updating the shadow dom),
     * not the textContent so attempting to use a Mutation Observer to detect these events
     * fails as well.
     */
    /* Autosave functionality for comment & discussion drafts */
    $.fn.autosave = function(opts) {
        var options = $.extend({interval: 60000, button: false}, opts);
        var textarea = this;
        if (!options.button)
            return false;

        var lastVal = $(textarea).val();

        /**
         * To prevent a race condition where a draft is saved while a form is being posted
         * we need to keep record of the post button
         */
        let submitButton = $(options.button).siblings(".DiscussionButton, .CommentButton");

        var save = function() {
            var currentVal = $(textarea).val();
            var defaultValues = [
                undefined,
                null,
                '',
                '[{\"insert\":\"\\n\"}]',
                lastVal
            ];
            /** This class is added to buttons while submission is in progress and is removed upon resolution */
            let isSubmitting = $(submitButton).hasClass("InProgress");
            if (!defaultValues.includes(currentVal) && !isSubmitting) {
                lastVal = currentVal;
                window.requestAnimationFrame(function () {
                    $(options.button).click();
                });
            }
        };

        if (options.interval > 0) {
            setInterval(save, options.interval);
        }

        return this;
    }
});
