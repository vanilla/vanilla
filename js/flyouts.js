/**
 * Legacy flyout code extracted from global.js
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * IFFE for flyout code.
 *
 * @param {Window} window
 * @param {jQuery} $
 */
(function(window, $) {
    var USE_NEW_FLYOUTS = gdn.getMeta("useNewFlyouts", false);
    var OPEN_CLASS = "Open";

    /**
     * Content load handler, which is fired on first load, and when additional content is loaded in.
     */
    $(document).on("contentLoad", function(e) {
        kludgeFlyoutHTML();
    });

    /**
     * Document ready handler. Runs only the first time the page is loaded.
     */
    $(function() {
        $(document).delegate(".Hijack, .js-hijack", "click", handleHijackClick);
        $(document).delegate(".ButtonGroup > .Handle", "click", handleButtonHandleClick);
        $(document).delegate(".ToggleFlyout", "click", handleToggleFlyoutClick);
        $(document).delegate(".ToggleFlyout a, .Dropdown a", "mouseup", handleToggleFlyoutMouseUp);
        $(document).delegate(".mobileFlyoutOverlay", "click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeAllFlyouts();
        });
        $(document).delegate(".Flyout, .Dropdown", "click", function (e) {
            e.stopPropagation();
        });
        $(document).on("click", function (e) {
            closeAllFlyouts();
        })
    });

    /**
     * Workarounds for limitations of flyout's HTML structure.
     */
    function kludgeFlyoutHTML() {
        var $handles = $(".ToggleFlyout, .editor-dropdown, .ButtonGroup");

        $handles.each(function() {
            $handles
                .find(".FlyoutButton, .Button-Options, .Handle, .editor-action:not(.editor-action-separator)")
                .each(function() {
                    $(this)
                        .attr("tabindex", "0")
                        .attr("role", "button")
                        .attr("aria-haspopup", "true");

                    $(this).accessibleFlyoutHandle(false);
                });

            $handles.find(".Flyout, .Dropdown").each(function() {
                $(this).accessibleFlyout(false);

                $(this)
                    .find("a")
                    .each(function() {
                        $(this).attr("tabindex", "0");
                    });
            });
        });

        if (USE_NEW_FLYOUTS) {
            var $contents = $(".Flyout, .ButtonGroup .Dropdown");
            var wrap = document.createElement("span");
            wrap.classList.add("mobileFlyoutOverlay");

            $contents.each(function() {
                var $item = $(this);
                if (!this.parentElement.classList.contains("mobileFlyoutOverlay")) {
                    $item.wrap(wrap);
                }

                // Some flyouts had conflicting inline display: none directly in the view.
                // We don't change that on open/close with the new style anymore so let's clean it up here.
                $item.removeAttr("style");
            });
        }

        // Button accessibility
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

    var BODY_CLASS = "flyoutIsOpen";

    /**
     * Close all flyouts and open the specified one.
     *
     * @param {JQuery} $toggleFlyout The flyout handle
     * @param {JQuery} $flyout The flyout body.
     */
    function openFlyout($toggleFlyout, $flyout) {
        closeAllFlyouts();

        $toggleFlyout
            .addClass(OPEN_CLASS)
            .closest(".Item")
            .addClass(OPEN_CLASS);

        if (!USE_NEW_FLYOUTS) {
            $flyout.show();
        }
        $toggleFlyout.setFlyoutAttributes();
        document.body.classList.add(BODY_CLASS);
    }

    /**
     * Close the specified flyout.
     *
     * @param {JQuery} $toggleFlyout The flyout handle
     * @param {JQuery} $flyout The flyout body.
     */
    function closeFlyout($toggleFlyout, $flyout) {
        if (!USE_NEW_FLYOUTS) {
            $flyout.hide();
        }
        $toggleFlyout
            .removeClass(OPEN_CLASS)
            .closest(".Item")
            .removeClass(OPEN_CLASS);
        $toggleFlyout.setFlyoutAttributes();
        document.body.classList.remove(BODY_CLASS);
    }

    /**
     * Close all flyouts, including ButtonGroups.
     */
    function closeAllFlyouts(e) {
        closeFlyout($(".ToggleFlyout"), $(".Flyout"));
        // Clear the button groups that are open as well.
        $(".ButtonGroup")
            .removeClass(OPEN_CLASS)
            .setFlyoutAttributes();

        // Kludge for legacy editor.
        $(".editor-dropdown-open")
            .removeClass("editor-dropdown-open")
            .setFlyoutAttributes();
        document.body.classList.remove(BODY_CLASS);
    }

    window.closeAllFlyouts = closeAllFlyouts;

    /**
     * Take over the clicking of an element in order to make a post request.
     *
     * @param {MouseEvent} e The click event.
     */
    function handleHijackClick(e) {
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
                $flyout = $toggleFlyout.find(".Flyout");
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
    }

    /**
     * Close existing flyouts and dropdowns and open the dropdown for a particular button handle.
     */
    function handleButtonHandleClick() {
        var $buttonGroup = $(this).closest(".ButtonGroup");
        var $isOpen = $buttonGroup.hasClass(OPEN_CLASS);
        closeAllFlyouts();
        if (!$isOpen) {
            // Open this one
            $buttonGroup.addClass(OPEN_CLASS).setFlyoutAttributes();
        }
        return false;
    }

    /**
     * Handle clicks on the flyout.
     *
     * @param {MouseEvent} e The click event to handle.
     */
    function handleToggleFlyoutClick(e) {
        var $toggleFlyout = $(this);
        var $flyout = $(".Flyout", this);
        var isHandle = false;

        if ($(e.target).closest(".Flyout").length === 0) {
            isHandle = true;
            e.stopPropagation();
        } else if (
            $(e.target).hasClass("Hijack") ||
            $(e.target)
                .closest("a")
                .hasClass("Hijack")
        ) {
            return;
        }
        e.stopPropagation();
        $toggleFlyout.fillFlyoutDynamically();

        // The old check.
        var isFlyoutClosed = $flyout.css("display") == "none";
        if (USE_NEW_FLYOUTS) {
            // The new check.
            isFlyoutClosed = !$toggleFlyout.hasClass(OPEN_CLASS);
        }

        // Toggling.
        if (isFlyoutClosed) {
            openFlyout($toggleFlyout, $flyout);
        } else {
            closeFlyout($toggleFlyout, $flyout);
        }

        if (isHandle) return false;
    }

    /**
     * Close all of the flyouts unless we are clicking on a button inside of a flyout.
     */
    function handleToggleFlyoutMouseUp() {
        if ($(this).hasClass("FlyoutButton")) return;
        closeAllFlyouts();
    }

    /**
     * jQuery function extensions
     */
    $.fn.extend({
        fillFlyoutDynamically: function() {
            var rel = $(this).attr("rel");
            if (rel) {
                $flyout = $(this).find(".Flyout");

                // Clear the rel and set a progress indicator.
                $(this).attr("rel", "");
                $flyout.html('<div class="InProgress" style="height: 30px"></div>');

                // Fetch the contents dynamically and fill on contents of the flyout.
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
        },
        accessibleFlyoutHandle: function(isOpen) {
            $(this).attr("aria-expanded", isOpen.toString());
        },

        accessibleFlyout: function(isOpen) {
            $(this).attr("aria-hidden", (!isOpen).toString());
        },

        setFlyoutAttributes: function() {
            $toggleFlyouts = $(this);
            $toggleFlyouts.each(function() {
                $toggle = $(this);
                var $handle = $(this).find(
                    ".FlyoutButton, .Button-Options, .Handle, .editor-action:not(.editor-action-separator)"
                );
                var $flyout = $(this).find(".Flyout, .Dropdown");
                var isOpen = $toggle.hasClass(OPEN_CLASS);

                $handle.accessibleFlyoutHandle(isOpen);
                $flyout.accessibleFlyout(isOpen);
            });
        },
    });
})(window, jQuery);
