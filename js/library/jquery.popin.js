/**
 * Popin elements
 * 
 * Allow elements with the .js-popin class to asynchronously load their contents from the server.
 */
"use strict";

(function($) {
    $(document).on("contentLoad", function(event) {
        var $target = $(event.target);

        $target.find(".js-popin").each(function(index, element){
            var $element = $(element);
            var rel = $element.attr("rel");

            if (typeof rel === "string" && rel.length !== 0) {
                $element.addClass("Loading");
                $.get(
                    gdn.url(rel),
                    { DeliveryType: "VIEW" },
                    function(data, textStatus, jqXHR) {
                        $element.removeClass("Loading");
                        $element.html(data);
                    },
                    "html"
                );
            }
        });
    })
})(jQuery);
