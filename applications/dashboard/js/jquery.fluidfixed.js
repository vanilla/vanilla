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
        };

        var debug = function(vars) {
            console.log('windowHeight    : ' + vars.windowHeight);
            console.log('documentHeight  : ' + vars.documentHeight);
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
            }

            if (handleScroll) {
                $(window).on("scroll." + vars.id, {element: element, vars: vars}, scrollHandler);
            } else {
                $(element).css('margin-top', 0);
                $(window).off("scroll." + vars.id);
            }
        };

        var scrollHandler = function(e) {
            var vars = e.data.vars;
            var element = e.data.element;
            vars.st = $(window).scrollTop();
            if (vars.st > vars.lastScrollTop){
                // downscroll
                handleDownScroll(element, vars.st, vars);
            } else if (vars.st < vars.lastScrollTop) {
                //upscroll
                handleUpScroll(element, vars.st, vars);
            }
            vars.lastScrollTop = vars.st;
        };

        var handleDownScroll = function(element, st, vars) {
            vars.upstart = 0;
            vars.upstartMargin = 0;

            if (vars.downstart == 0) {
                vars.downstart = st;
            }

            if (vars.downstartMargin == 0) {
                var px = $(element).css('margin-top');
                vars.downstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.max((vars.downstartMargin + vars.downstart - st), vars.maxMarginDiff);

            if (st > vars.offsetTop) {
                $(element).css('margin-top', margin + 'px');
            } else {
                $(element).css('margin-top', - (st) + 'px');
            }
        };

        var handleUpScroll = function(element, st, vars) {
            vars.downstart = 0;
            vars.downstartMargin = 0;

            if (vars.upstart == 0) {
                vars.upstart = st;
            }

            if (vars.upstartMargin == 0) {
                var px = $(element).css('margin-top');
                vars.upstartMargin = parseInt(px.substring(0, px.length - 2));
            }

            var margin = Math.min((vars.upstartMargin - st + vars.upstart), 0);
            $(element).css('margin-top', margin + 'px');
        }

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
                lastScrollTop: $(window).scrollTop(),
                st: $(window).scrollTop()
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
                $(window).off("scroll." + vars.id);
            });

            start(self, vars);
        });
    };

    $.fn.fluidfixed.defaults = {
        offsetTop: undefined,
        offsetBottom: undefined
    };

})(jQuery);
