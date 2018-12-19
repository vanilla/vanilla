/**
 * Legacy flyout code extracted from global.js
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// Global vanilla library function.
(function(window, $) {
    var USE_NEW_FLYOUTS = gdn.getMeta("Feature.NewFlyouts.Enabled", false);

    function accessibleButtonsInit() {
        $(document).delegate("[role=button]", "keydown", function(event) {
            var $button = $(this);
            var ENTER_KEY = 13;
            var SPACE_KEY = 32;
            var isActiveElement = document.activeElement === $button[0];
            var isSpaceOrEnter = event.keyCode === ENTER_KEY || event.keyCode === SPACE_KEY;
            if (isActiveElement && isSpaceOrEnter) {
                event.preventDefault();
                $button.click();
            }
        });
    }

    function openFlyout($toggleFlyout, $flyout) {
        // Opening a flyout will also close any button groups.
        console.log("Clear button groups");
        $(".ButtonGroup")
            .removeClass("Open")
            .setFlyoutAttributes();
        
        
        $toggleFlyout
            .addClass("Open")
            .closest(".Item")
            .addClass("Open");
        $flyout.show();
        $toggleFlyout.setFlyoutAttributes();
    }

    function closeFlyout($toggleFlyout, $flyout) {
        $flyout.hide();
        $toggleFlyout
            .removeClass("Open")
            .closest(".Item")
            .removeClass("Open");
        
        $toggleFlyout.setFlyoutAttributes();
    }

    function closeAllFlyouts() {
        closeFlyout($(".ToggleFlyout"), $(".Flyout"))
    }

    $(document).on("contentLoad", function(e) {
        // Set up accessible flyouts
        $(".ToggleFlyout, .editor-dropdown, .ButtonGroup").accessibleFlyoutsInit();
        accessibleButtonsInit();
    });

    $(function() {
        var hijackClick = function(e) {
            var $elem = $(this);
            var $parent = $(this).closest(".Item");
            var $toggleFlyout = $elem.closest(".ToggleFlyout");
            var href = $elem.attr("href");
            var progressClass = $elem.hasClass("Bookmark") ? "Bookmarking" : "InProgress";

            // If empty, or starts with a fragment identifier, do not send
            // an async request.
            if (!href || href.trim().indexOf("#") === 0) return;
            gdn.disable(this, progressClass);
            e.stopPropagation();

            $.ajax({
                type: "POST",
                url: href,
                data: { DeliveryType: "VIEW", DeliveryMethod: "JSON", TransientKey: gdn.definition("TransientKey") },
                dataType: "json",
                complete: function() {
                    gdn.enable($elem.get(0));
                    $elem.removeClass(progressClass);
                    $elem.attr("href", href);
                    $flyout = $toggleFlyout.find('.Flyout');

                    closeFlyout($toggleFlyout, $flyout);
                },
                error: function(xhr) {
                    gdn.informError(xhr);
                },
                success: function(json) {
                    if (json === null) json = {};

                    var informed = gdn.inform(json);
                    gdn.processTargets(json.Targets, $elem, $parent);
                    // If there is a redirect url, go to it.
                    if (json.RedirectTo) {
                        setTimeout(function() {
                            window.location.replace(json.RedirectTo);
                        }, informed ? 3000 : 0);
                    }
                },
            });

            return false;
        };
        $(document).delegate(".Hijack, .js-hijack", "click", hijackClick);

        // Activate ToggleFlyout and ButtonGroup menus
        $(document).delegate(".ButtonGroup > .Handle", "click", function() {
            var $buttonGroup = $(this).closest(".ButtonGroup");
            if (!$buttonGroup.hasClass("Open")) {
                $(".ButtonGroup")
                    .removeClass("Open")
                    .setFlyoutAttributes();

                // Open this one
                $buttonGroup.addClass("Open").setFlyoutAttributes();
            } else {
                $(".ButtonGroup")
                    .removeClass("Open")
                    .setFlyoutAttributes();
            }
            return false;
        });

        var lastOpen = null;

        $(document).delegate(".ToggleFlyout", "click", function(e) {
            var $toggleFlyout = $(this);
            var $flyout = $(".Flyout", this);
            var isHandle = false;

            if ($(e.target).closest(".Flyout").length === 0) {
                e.stopPropagation();
                isHandle = true;
            } else if (
                $(e.target).hasClass("Hijack") ||
                $(e.target)
                    .closest("a")
                    .hasClass("Hijack")
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
                    },
                });
            }

            // The old check.
            var isFlyoutClosed = $flyout.css("display") == "none";
            if (USE_NEW_FLYOUTS) {
                // The new check.
                isFlyoutClosed = $flyout.is(":visible");
            }

            if (isFlyoutClosed) {
                if (lastOpen !== null) {
                    $lastOpenFlyout = $(".Flyout", lastOpen);
                    $lastOpenToggleFlyout = $(lastOpen);
                    closeFlyout($lastOpenToggleFlyout, $lastOpenFlyout);
                }

                openFlyout($toggleFlyout, $flyout);
                lastOpen = this;
            } else {
                closeFlyout($toggleFlyout, $flyout);
            }

            if (isHandle) return false;
        });

        // Close ToggleFlyout menu even if their links are hijacked
        $(document).delegate(".ToggleFlyout a", "mouseup", function() {
            if ($(this).hasClass("FlyoutButton")) return;
            closeFlyout($(".ToggleFlyout"), $(".Flyout"))
            $(this)
                .closest(".ToggleFlyout")
                .setFlyoutAttributes();
        });

        $(document).delegate(document, "click", function() {
            if (lastOpen) {
                $toggleFlyout = $(lastOpen);
                $flyout = $(".Flyout", lastOpen);
                closeFlyout($toggleFlyout, $flyout);
            }
        });
    });

    $.fn.extend({
        accessibleFlyoutHandle: function(isOpen) {
            $(this).attr("aria-expanded", isOpen.toString());
        },

        accessibleFlyout: function(isOpen) {
            $(this).attr("aria-hidden", (!isOpen).toString());
        },

        setFlyoutAttributes: function() {
            $(this).each(function() {
                var $handle = $(this).find(
                    ".FlyoutButton, .Button-Options, .Handle, .editor-action:not(.editor-action-separator)",
                );
                var $flyout = $(this).find(".Flyout, .Dropdown");
                var isOpen = $flyout.is(":visible");

                $handle.accessibleFlyoutHandle(isOpen);
                $flyout.accessibleFlyout(isOpen);
            });
        },

        accessibleFlyoutsInit: function() {
            var $context = $(this);

            $context.each(function() {
                $context
                    .find(".FlyoutButton, .Button-Options, .Handle, .editor-action:not(.editor-action-separator)")
                    .each(function() {
                        $(this)
                            .attr("tabindex", "0")
                            .attr("role", "button")
                            .attr("aria-haspopup", "true");

                        $(this).accessibleFlyoutHandle(false);
                    });

                $context.find(".Flyout, .Dropdown").each(function() {
                    $(this).accessibleFlyout(false);

                    $(this)
                        .find("a")
                        .each(function() {
                            $(this).attr("tabindex", "0");
                        });
                });
            });
        },
    });
})(window, jQuery);
