/**
 * An element with the js-popin class can be populated with content from a URL by specifying it in the rel attribute.
 * @version 1.0
 * @requires jQuery
 */
"use strict";

(function($) {
    $(document).on("contentLoad", function(event) {
        var $target = $(event.target);

        // Search the element firing the contentLoad event for any pop-in elements.
        $target.find(".js-popin").each(function(index, element){
            var $element = $(element);
            var rel = $element.attr("rel");

            // Do we have a workable URL?
            if (typeof rel === "string" && rel.length !== 0) {
                // Slap some styling on the element to indicate the request is in progress.
                $element.addClass("Loading");

                // Perform the request for content.  We only want content, so skip the master view with DeliveryType.
                $.get(
                    gdn.url(rel),
                    { DeliveryType: "VIEW" },
                    function(data, textStatus, jqXHR) {
                        // Remove that progress indicator styling.
                        $element.removeClass("Loading");

                        // Populate the current element with the server's response.
                        $element.html(data);
                    },
                    "html"
                );
            }
        });
    })
})(jQuery);
