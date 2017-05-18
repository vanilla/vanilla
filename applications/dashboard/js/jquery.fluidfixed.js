/**
 * Makes a fluidfixed item that, if taller than the window height, will scroll up when the user scrolls up
 * and scroll down when the user scrolls down.
 */

(function($) {
    /**
     * Start fluidfixed on an element. To do so, use $('js-fluidfixed').fluidfixed();
     *
     * As options, it accepts two properties:
     * offsetTop: The offset from the top of the window that it will stop at when scrolling up.
     * offsetBottom: The offset from the bottom of the window that it will stop at when scrolling down.
     *
     * If not passed, the offsetTop will default to the offset of the fluidfixed element to the top of the window.
     * This is likely what you want, but you can customize it. However, you'll probably want to define your
     * offsetBottom. It defaults to 30px.
     *
     * @param {{offsetTop: {(undefined|number)}, offsetBottom: {(undefined|number)}}} options
     */
    $.fn.fluidfixed = function(options) {

        /**
         *
         * @type {Array}
         */
        var killThreadIds = [];

        /**
         * Resets the variables.
         *
         * @param {FluidFixedSettings} vars
         */
        var reset = function(vars) {
            vars.windowHeight = undefined;
            vars.containerHeight = undefined;
            vars.documentHeight = undefined;
            vars.minMargin = undefined;
            vars.upstartMargin = 0;
            vars.downstartMargin = 0;
            vars.upstart = 0;
            vars.downstart = 0;
            vars.lastMarginTop = 0;
        };

        /**
         * Prints out the variables.
         *
         * @param {FluidFixedSettings} vars
         */
        var debug = function(vars) {
            console.log('windowHeight    : ' + vars.windowHeight);
            console.log('documentHeight  : ' + vars.documentHeight);
            console.log('containerHeight : ' + vars.containerHeight);
            console.log('minMargin       : ' + vars.minMargin);
            console.log('upstartMargin   : ' + vars.upstartMargin);
            console.log('downstartMargin : ' + vars.downstartMargin);
            console.log('upstart         : ' + vars.upstart);
            console.log('downstart       : ' + vars.downstart);
        };


        /**
         *
         * @param element
         * @param {FluidFixedSettings} vars
         */
        var start = function(element, vars) {

            // First, calculate the offsets and heights.

            if (vars.containerHeight == undefined) {
                vars.containerHeight = $(element).outerHeight(true);
            }

            if (vars.documentHeight == undefined) {
                vars.documentHeight = $(document).height();
            }

            if (vars.windowHeight == undefined) {
                vars.windowHeight = $(window).height();
            }

            if (vars.minMargin == undefined) {
                // The lowest negative margin on the container.
                vars.minMargin = - (vars.containerHeight + vars.offsetTop + vars.offsetBottom - vars.windowHeight);
            }

            /**
             * We have 3 cases to check here:
             *
             * 1. The fluidfixed element is higher than the page height, so we don't need to do anything.
             * 2. The fluidfixed element height is shorter than the window height, so we can just set
             *    the fluidfixed element's position to fixed.
             * 3. The fluidfixed element height is higher than the window height, so we need to set the
             *    fluidfixed element's position to fixed and calculate the position by adjusting the margin
             *    when scrolling.
             */

            var containerOuterHeight = vars.containerHeight + vars.offsetTop + vars.offsetBottom;
            var fixObject = containerOuterHeight < vars.documentHeight && containerOuterHeight < vars.windowHeight;

            var handleScroll = fixObject
                && containerOuterHeight > vars.windowHeight // Element height is higher than the window height
                && vars.documentHeight > vars.windowHeight; // Page height is higher than window height

            if (fixObject) {
                $(element).css('position', 'fixed');

                if ((vars.scrollTop + containerOuterHeight) > vars.documentHeight) {
                    // We're loading the page near the bottom of the document, the fluidfixed element will be cut
                    // off unless we set the margin-top to the minMargin.
                    $(element).css('margin-top', vars.minMargin);
                }

                if (handleScroll) {
                    // Case 3: The element needs to be fixed and we have to calculate its position on scroll.
                    var $element = $(element); // Cache element before scroll.
                    killThreadIds[vars.id] = false;
                    scrollHandler($element, vars);
                } else {
                    // Case 2: The element only needs to be fixed, no position calculations necessary.
                    $(element).css('margin-top', 0);
                    killThreadIds[vars.id] = true;
                    $(window).off("scroll." + vars.id); // IE
                }

            } else {
                // Case 1: The element doesn't need to be fixed, reinstate its original position property.
                $(element).css('position', vars.position);
            }
        };

        /**
         * Detect request animation frame.
         *
         * @param {jQuery} $element
         * @param {FluidFixedSettings} vars
         * @returns {*|Function}
         */
        var getRequestAnimationFrame = function($element, vars) {
            return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.msRequestAnimationFrame ||
            window.oRequestAnimationFrame ||
            // IE Fallback
            function() { $(window).on("scroll." + vars.id, $.proxy(onScroll, null, $element, vars)); };
        };

        /**
         * Starts an infinite loop using the requestAnimationFrame which lets us execute code on the
         * next available screen repaint, rather than trying to calculate the position when the browser's
         * not ready for it.
         *
         * @param {jQuery} $element
         * @param {FluidFixedSettings} vars
         * @returns {boolean}
         */
        var scrollHandler = function($element, vars) {
            if (killThreadIds[vars.id]) {
                // We've been interrupted. Kill the loop.
                return false;
            }
            if (vars.lastScrollTop == window.pageYOffset) {
                // We're not scrolling.
                getRequestAnimationFrame($element, vars)($.proxy(scrollHandler, null, $element, vars));
                return false;
            } else {
                // We're scrolling, let's party.
                onScroll($element, vars);
                getRequestAnimationFrame($element, vars)($.proxy(scrollHandler, null, $element, vars));
            }
        };

        /**
         * Checks to see whether we're scrolling up or down by comparing the current scroll position with the last
         * calculated scroll position, then handles the positioning.
         *
         * @param {jQuery} $element
         * @param {FluidFixedSettings} vars
         */
        var onScroll = function($element, vars) {
            vars.scrollTop = window.pageYOffset;
            if (vars.scrollTop > vars.lastScrollTop) {
                // downscroll
                handleDownScroll($element, vars);
            } else if (vars.scrollTop < vars.lastScrollTop) {
                //upscroll
                handleUpScroll($element, vars);
            }
            vars.lastScrollTop = vars.scrollTop;
        };

        /**
         * Calculates our and applies our downscroll position. Works by incrementally adding a negative margin
         * to the fluidfixed element.
         *
         * @param {jQuery} $element
         * @param {FluidFixedSettings} vars
         */
        var handleDownScroll = function($element, vars) {
            vars.upstart = 0;
            vars.upstartMargin = 0;

            if (vars.downstart != 0 && vars.lastMarginTop == vars.minMargin) {
                // We're scrolling down and the margin is already at the min margin, we can't decrease the
                // margin anymore, so checkout early.
                return;
            }

            if (vars.downstart == 0) {
                vars.downstart = vars.scrollTop;
            }

            if (vars.downstartMargin == 0) {
                var px = $element.css('margin-top');
                vars.downstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.max((vars.downstartMargin + vars.downstart - vars.scrollTop), vars.minMargin);

            if (vars.scrollTop > vars.offsetTop) {
                vars.lastMarginTop = margin;
                $element.css('margin-top', margin + 'px');
            } else {
                vars.lastMarginTop = - (vars.scrollTop);
                $element.css('margin-top', - (vars.scrollTop) + 'px');
            }
        };

        /**
         * Calculates our and applies our upscroll position. Works by incrementally removing the negative margin
         * on the fluidfixed element.
         *
         * @param {jQuery} $element
         * @param {FluidFixedSettings} vars
         */
        var handleUpScroll = function($element, vars) {
            vars.downstart = 0;
            vars.downstartMargin = 0;

            if (vars.upstart != 0 && vars.lastMarginTop == 0) {
                // We're scrolling up and the margin is already 0. We can't increase the margin any further,
                // so check out early.
                return;
            }

            if (vars.upstart == 0) {
                vars.upstart = vars.scrollTop;
            }

            if (vars.upstartMargin == 0) {
                var px = $element.css('margin-top');
                vars.upstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.min((vars.upstartMargin - vars.scrollTop + vars.upstart), 0);
            vars.lastMarginTop = margin;
            $element.css('margin-top', margin + 'px');
        };

        /**
         * Initializes the settings for each fluidfixed element and starts the whole operation.
         */
        this.each(function() {
            var self = this;

            // Extend Settings.
            var settings = $.extend({}, $.fn.fluidfixed.defaults, options);

            if (settings.offsetTop === undefined) {
                settings.offsetTop = $(this).offset().top;
            }

            if (settings.offsetBottom === undefined) {
                settings.offsetBottom = 30;
            }

            var position = 'static';

            if ($(this).css('position') !== null) {
                position = $(this).css('position');
            }

            /**
             * The variables we pass around for each fluidfixed object.
             *
             * @typedef {Object} FluidFixedSettings
             * @property {string} id - A unique ID generated for the fluidfixed element used to kill the scroll listener if necessary. This has nothing to do with the id property on an HTML element.
             * @property {number} offsetTop - The offset between the top of the window and the top of the container when scrolling up (set to offset of container by default).
             * @property {number} offsetBottom - The offset between the bottom of the window and the bottom of the container when scrolling down (set to 30 by default).
             * @property {(number|undefined)} windowHeight - The window height.
             * @property {(number|undefined)} containerHeight - The height of the fluidfixed element.
             * @property {(number|undefined)} documentHeight - The height of the document/page.
             * @property {(number|undefined)} minMargin - The lowest the negative margin can get when we're scrolling down.
             * @property {number} upstartMargin - The margin on the container when we start scrolling up.
             * @property {number} downstartMargin - The margin on the container when we start scrolling down.
             * @property {number} upstart - The offset of the container when we start scrolling up.
             * @property {number} downstart - The offset of the container when we start scrolling down.
             * @property {number} lastMarginTop - The last calculated margin-top.
             * @property {number} lastScrollTop - The last calcuated window offset, used to find whether we're scrolling up or down.
             * @property {number} scrollTop - The current window offset.
             * @property {string} position - The old CSS position property to revert to if fluidfixed is detached from the element, defaults to 'static'.
             */

            /**
             * @type {FluidFixedSettings}
             */
            var vars = {
                id: Math.random().toString(36).substr(2, 9),
                offsetTop: settings.offsetTop,
                offsetBottom: settings.offsetBottom,
                windowHeight: undefined,
                containerHeight: undefined,
                documentHeight: undefined,
                minMargin: undefined,
                upstartMargin: 0,
                downstartMargin: 0,
                upstart: 0,
                downstart: 0,
                lastMarginTop: 0,
                lastScrollTop: window.pageYOffset,
                scrollTop: window.pageYOffset,
                position: position
            };

            /**
             * Kills the fluidfixed functionality of a fluidfixed element.
             */
            $(self).on('detach.FluidFixed', function() {
                $(self).css('position', vars.position);
                $(self).css('margin-top', 0);
                killThreadIds[vars.id] = true;
                $(window).off("scroll." + vars.id); // IE
            });

            /**
             * Resets and recalculates the fluidfixed element position when the `reset.FluidFixed` event is triggered.
             */
            $(self).on('reset.FluidFixed', function() {
                reset(vars);
                start(self, vars);
            });

            /**
             * Resets and recalculates the fluidfixed element position when the window is resized.
             */
            $(window).resize(function() {
                reset(vars);
                start(self, vars);
            });

            start(self, vars);
        });
    };

    /**
     * The properties that need to exist in the options being passed in initialization.
     *
     * @type {{offsetTop: {(undefined|number)}, offsetBottom: {(undefined|number)}}}
     */
    $.fn.fluidfixed.defaults = {
        offsetTop: undefined,
        offsetBottom: undefined
    };

})(jQuery);
