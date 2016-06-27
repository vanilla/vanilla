/**
 * A utility for attaching listeners and handling events associated with spoiler visibility toggling.
 */
var spoilers = {

    /**
     * Gather all UserSpoiler DIVs and attempt to augment them with spoiler capabilities.
     */
    findAndReplace: function() {
        jQuery("div.Spoiler", this).each(function(i, el) {
            spoilers.replaceSpoiler(el);
        });
    },

    /**
     * Add spoiler capabilities to a specific HTML element.
     * @param {HTMLElement} spoiler The element we're adding hide/show capabilities to.
     */
    replaceSpoiler: function(spoiler) {
        var spoilerObject = jQuery(spoiler);

        // Has this element already been setup as a spoiler? Skip it.
        if (spoilerObject.hasClass("SpoilerConfigured")) {
            return;
        }

        // Build the individual elements.
        var text = document.createElement("div");
        text.className = "SpoilerText";
        while(spoiler.firstChild) {
            text.appendChild(spoiler.firstChild);
        }

        var title = document.createElement("div");
        title.className = "SpoilerTitle";
        title.innerHTML = gdn.definition("Spoiler", "Spoiler");

        var reveal = document.createElement("div");
        reveal.className = "SpoilerReveal";

        var spoilerButton       = document.createElement("input");
        spoilerButton.type      = "button";
        spoilerButton.value     = gdn.definition("show", "show");
        spoilerButton.className = "SpoilerToggle";

        // Assemble the elements.
        spoiler.appendChild(title);
        spoiler.appendChild(reveal);
        spoiler.appendChild(text);
        title.appendChild(spoilerButton);

        // Add an event listener to the toggle button.
        spoilerObject.on("click", "input.SpoilerToggle", function(event) {
            event.stopPropagation();
            spoilers.toggleSpoiler(jQuery(event.delegateTarget), jQuery(event.target));
        });

        // Flag this spoiler as setup.
        spoilerObject.addClass("SpoilerConfigured");
    },

    /**
     * Toggle a spoiler's visibility on or off.
     * @param {object} spoiler The primary spoiler HTML element.
     * @param {object} spoilerButton The button HTML element used to trigger the toggle.
     */
    toggleSpoiler: function(spoiler, spoilerButton) {
        var thisSpoilerText   = spoiler.find("div.SpoilerText").first();
        var thisSpoilerStatus = thisSpoilerText.css("display");
        var newSpoilerStatus  = (thisSpoilerStatus === "none") ? "block" : "none";

        thisSpoilerText.css("display", newSpoilerStatus);

        if (newSpoilerStatus === "none") {
            spoilerButton.val(gdn.definition("show", "show"));
        } else {
            spoilerButton.val(gdn.definition("hide", "hide"));
        }
    }
};

jQuery(document).on("contentLoad", function(e) {
    spoilers.findAndReplace.apply(e.target);
});
