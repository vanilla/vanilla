/**
 * Makes a fluid fixed item that, if taller than the window height, will scroll up when the user scrolls up
 * and scroll down when the user scrolls down.
 */
(function($) {
    $.fn.fluidfixed = function(options) {

        var reset = function(vars) {
            vars.windowHeight = undefined;
            vars.containerHeight = undefined;
            vars.documentHeight = undefined;
            vars.maxMarginDiff = undefined;
            vars.upstartMargin = 0;
            vars.downstartMargin = 0;
            vars.upstart = 0;
            vars.downstart = 0;
            vars.lastMarginTop = 0;
        };

        var debug = function(vars) {
            console.log('windowHeight    : ' + vars.windowHeight);
            console.log('documentHeight  : ' + vars.documentHeight);
            console.log('containerHeight : ' + vars.containerHeight);
            console.log('maxMarginDiff   : ' + vars.maxMarginDiff);
            console.log('upstartMargin   : ' + vars.upstartMargin);
            console.log('downstartMargin : ' + vars.downstartMargin);
            console.log('upstart         : ' + vars.upstart);
            console.log('downstart       : ' + vars.downstart);
        };


        var start = function(element, vars) {

            if (vars.containerHeight == undefined) {
                var px = $(element).css('margin-top');
                var margin = parseInt(px.substring(0, px.length - 2));
                vars.containerHeight = $(element).outerHeight(true) - margin;
            }

            if (vars.documentHeight == undefined) {
                vars.documentHeight = $(document).height();
            }

            if (vars.windowHeight == undefined) {
                vars.windowHeight = $(window).height();
            }

            if (vars.maxMarginDiff == undefined) {
                // The max negative margin on the container
                vars.maxMarginDiff = - (vars.containerHeight + vars.offsetTop + vars.offsetBottom - vars.windowHeight);
            }

            // Page height is higher than the element height
            var fixObject = (vars.containerHeight + vars.offsetTop + vars.offsetBottom) < vars.documentHeight;

            var handleScroll = fixObject
                && vars.containerHeight + vars.offsetTop + vars.offsetBottom > vars.windowHeight // Element height is higher than the viewport height
                && vars.documentHeight > vars.windowHeight; // Page height is higher than viewport height

            if (fixObject) {
                $(element).css('position', 'fixed');

                if ((vars.st + vars.containerHeight + vars.offsetTop + vars.offsetBottom) > vars.documentHeight) {
                    // We're going to extend past the page bottom.
                    $(element).css('margin-top', vars.maxMarginDiff);
                }
            } else {
                $(element).css('position', vars.position);
            }

            if (handleScroll) {
                var $element = $(element); // Cache element before scroll.
                killThreadIds[vars.id] = false;
                scrollHandler($element, vars);
            } else {
                $(element).css('margin-top', 0);
                killThreadIds[vars.id] = true;
                $(window).off("scroll." + vars.id); // IE
            }
        };

        // Detect request animation frame
        var scroll = function($element, vars) {
            return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.msRequestAnimationFrame ||
            window.oRequestAnimationFrame ||
            // IE Fallback
            function() { $(window).on("scroll." + vars.id, $.proxy(onScroll, null, $element, vars)); };
        }

        var scrollHandler = function($element, vars) {
            if (killThreadIds[vars.id]) {
                return false;
            }
            if (vars.lastScrollTop == window.pageYOffset) {
                scroll($element, vars)($.proxy(scrollHandler, null, $element, vars));
                return false;
            } else {
                onScroll($element, vars);
                scroll($element, vars)($.proxy(scrollHandler, null, $element, vars));
            }
        };

        var onScroll = function($element, vars) {
            vars.st = window.pageYOffset;
            if (vars.st > vars.lastScrollTop) {
                // downscroll
                handleDownScroll($element, vars);
            } else if (vars.st < vars.lastScrollTop) {
                //upscroll
                handleUpScroll($element, vars);
            }
            vars.lastScrollTop = vars.st;
        }

        var handleDownScroll = function($element, vars) {
            vars.upstart = 0;
            vars.upstartMargin = 0;

            if (vars.downstart != 0 && vars.lastMarginTop == vars.maxMarginDiff) {
                // checkout early
                return;
            }

            if (vars.downstart == 0) {
                vars.downstart = vars.st;
            }

            if (vars.downstartMargin == 0) {
                var px = $element.css('margin-top');
                vars.downstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.max((vars.downstartMargin + vars.downstart - vars.st), vars.maxMarginDiff);

            if (vars.st > vars.offsetTop) {
                vars.lastMarginTop = margin;
                $element.css('margin-top', margin + 'px');
            } else {
                vars.lastMarginTop = - (vars.st);
                $element.css('margin-top', - (vars.st) + 'px');
            }
        };

        var handleUpScroll = function($element, vars) {
            vars.downstart = 0;
            vars.downstartMargin = 0;

            if (vars.upstart != 0 && vars.lastMarginTop == 0) {
                // checkout early
                return;
            }

            if (vars.upstart == 0) {
                vars.upstart = vars.st;
            }

            if (vars.upstartMargin == 0) {
                var px = $element.css('margin-top');
                vars.upstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.min((vars.upstartMargin - vars.st + vars.upstart), 0);
            vars.lastMarginTop = margin;
            $element.css('margin-top', margin + 'px');
        }

        var killThreadIds = [];

        this.each(function() {
            var self = this;

            // Extend Settings.
            var settings = $.extend({}, $.fn.fluidfixed.defaults, options);

            if (settings.offsetTop === undefined) {
                settings.offsetTop = $(this).offset().top;
            }

            if (settings.offsetBottom === undefined) {
                settings.offsetBottom = 0;
            }

            var position = 'static';

            if ($(this).css('position') !== null) {
                position = $(this).css('position');
            }

            var vars = {
                id: Math.random().toString(36).substr(2, 9),
                offsetTop: settings.offsetTop,
                offsetBottom: settings.offsetBottom,
                windowHeight: undefined,
                containerHeight: undefined,
                documentHeight: undefined,
                maxMarginDiff: undefined,
                upstartMargin: 0,
                downstartMargin: 0,
                upstart: 0,
                downstart: 0,
                lastMarginTop: 0,
                lastScrollTop: window.pageYOffset,
                st: window.pageYOffset,
                position: position
            };

            $(self).on('reset.FluidFixed', function() {
                reset(vars);
                start(self, vars);
            });

            $(window).resize(function() {
                reset(vars);
                start(self, vars);
            });

            $(self).on('detach.FluidFixed', function() {
                $(self).css('position', 'initial');
                $(self).css('margin-top', 0);
                killThreadIds[vars.id] = true;
                $(window).off("scroll." + vars.id); // IE
            });

            start(self, vars);
        });
    };

    $.fn.fluidfixed.defaults = {
        offsetTop: undefined,
        offsetBottom: undefined
    };

})(jQuery);
