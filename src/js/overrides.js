/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {disableScroll, enableScroll, toggleScroll} from './utility';

/**
 * Resets this listener
 * https://github.com/vanilla/vanilla/blob/f751e382da325e05784ba918016b1af2902f3c3a/js/global.js#L790
 * in order to work visibility:hidden instead of display:none
 *
 * The main js file should not rely on certain CSS styles!!!
 */
export function fixToggleFlyoutBehaviour() {
    $(document).undelegate(".ToggleFlyout", "click");
    var lastOpen = null;
    $(document).delegate(".ToggleFlyout", "click", function(e) {
        var $flyout = $(".Flyout", this);
        var isHandle = false;

        if ($(e.target).closest(".Flyout").length === 0) {
            e.stopPropagation();
            isHandle = true;
        } else if (
            $(e.target).hasClass("Hijack") ||
            $(e.target).closest("a").hasClass("Hijack")
        ) {
            return;
        }
        e.stopPropagation();

        // Dynamically fill the flyout.
        var rel = $(this).attr("rel");
        if (rel) {
            $(this).attr("rel", "");
            $flyout.html('<div class="InProgress" style="height: 30px"></div>');

            $.ajax({
                url: gdn.url(rel),
                data: { DeliveryType: "VIEW" },
                success: function(data) {
                    $flyout.html(data);
                },
                error: function(xhr) {
                    $flyout.html("");
                    gdn.informError(xhr, true);
                }
            });
        }

        if ($flyout.css("visibility") == "hidden") {
            if (lastOpen !== null) {
                $(".Flyout", lastOpen).hide();
                $(lastOpen)
                    .removeClass("Open")
                    .closest(".Item")
                    .removeClass("Open");
            }

            $(this).addClass("Open").closest(".Item").addClass("Open");
            $flyout.show();
            disableScroll();
            lastOpen = this;
        } else {
            $flyout.hide();
            $(this).removeClass("Open").closest(".Item").removeClass("Open");
            enableScroll();
        }

        if (isHandle) return false;
    });

    // Close ToggleFlyout menu even if their links are hijacked
    $(document).delegate('.ToggleFlyout a', 'mouseup', function() {
        if ($(this).hasClass('FlyoutButton'))
            return;

        $('.ToggleFlyout').removeClass('Open').closest('.Item').removeClass('Open');
        $('.Flyout').hide();
    });

    $(document).on( "click touchstart", function() {
        if (lastOpen) {
            $(".Flyout", lastOpen).hide();
            $(lastOpen)
                .removeClass("Open")
                .closest(".Item")
                .removeClass("Open");
        }
        $(".ButtonGroup").removeClass("Open");
        enableScroll();
    });

    $(".Button.Primary.Handle").on("click", event => {
        toggleScroll();
    });

    $(".Options .Flyout").on("click", () => {
        enableScroll();
    });
}
