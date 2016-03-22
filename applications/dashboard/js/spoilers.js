/**
 * A utility for attaching listeners and handling events associated with spoiler visibility toggling.
 */
var spoilers = {

    /**
     * Gather all UserSpoiler DIVs and attempt to augment them with spoiler capabilities.
     */
    findAndReplace: function() {
        jQuery('div.UserSpoiler').each(function(i, el) {
            spoilers.replaceSpoiler(el);
        });
    },

    /**
     * Add spoiler capabilities to a specific HTML element.
     * @param {HTMLElement} spoiler The element we're adding hide/show capabilities to.
     */
    replaceSpoiler: function(spoiler) {
        // Don't re-event spoilers that are already 'on'
        if (spoiler.spoilerFunctioning === true) {
            return;
        }

        // Extend object with jQuery
        spoilerObject = jQuery(spoiler);

        var spoilerTitle        = spoilerObject.find('div.SpoilerTitle').first();
        var spoilerButton       = document.createElement('input');
        spoilerButton.type      = 'button';
        spoilerButton.value     = gdn.definition('show', 'show');
        spoilerButton.className = 'SpoilerToggle';

        spoilerTitle.append(spoilerButton);

        spoilerObject.on('click', 'input.SpoilerToggle', function(event) {
            event.stopPropagation();
            spoilers.toggleSpoiler(jQuery(event.delegateTarget), jQuery(event.target));
        });

        spoiler.spoilerFunctioning = true;
    },

    /**
     * Toggle a spoiler's visibility on or off.
     * @param {object} spoiler The primary spoiler HTML element.
     * @param {object} spoilerButton The button HTML element used to trigger the toggle.
     */
    toggleSpoiler: function(spoiler, spoilerButton) {
        var thisSpoilerText   = spoiler.find('div.SpoilerText').first();
        var thisSpoilerStatus = thisSpoilerText.css('display');
        var newSpoilerStatus  = (thisSpoilerStatus === 'none') ? 'block' : 'none';

        thisSpoilerText.css('display', newSpoilerStatus);

        if (newSpoilerStatus === 'none') {
            spoilerButton.val(gdn.definition('show', 'show'));
        } else {
            spoilerButton.val(gdn.definition('hide', 'hide'));
        }
    }
};

// Events!
jQuery(document).ready(function(){
    spoilers.findAndReplace();
});

jQuery(document).on('CommentPagingComplete CommentAdded MessageAdded PreviewLoaded popupReveal', function() {
    spoilers.findAndReplace();
});
