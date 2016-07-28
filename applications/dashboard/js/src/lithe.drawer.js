/**
 * Drawer component for the Lithe mobile theme
 *
 * @author  Kasper Isager <kasper@vanillaforums.com>
 */
;(function ($, window, document, undefined) {
    'use strict';

    /**
     * @param   element The context in which the component was called
     * @param   options Options passed along when initializing the plugin
     * @constructor
     */
    Lithe.Drawer = function (element, options) {
        var self = this;

        self.element = element;

        self.options = {
            toggle      : undefined, // Button, link or other element to toggle the drawer
            container   : undefined, // The container to attach the classes to
            content     : undefined, // The content, collapses the drawer when clicked
            classes     : {
                show: 'drawer-show',
                hide: 'drawer-hide'
            }
        };

        if (options) {
            jQuery.extend(self.options, options);
        }

        self._enable();
    };

    Lithe.Drawer.prototype = {
        /**
         * Tear down the component
         *
         * @private
         */
        _destroy: function () {
            var self = this;

            self._disable();

            jQuery(self.element).removeData('litheDrawer');
        },

        /**
         * Get/change options AFTER initialization.
         *
         * @this    {Drawer}
         * @param   key     The option we wish to change
         * @param   value   The value we wish to assign to it
         * @returns {*}
         */
        option: function (key, value) {
            var self, options;

            self    = this;
            options = self.options;

            self._disable();

            if (key && value === 'undefined') {
                return options[key];
            }

            if (jQuery.isPlainObject(key)) {
                options = jQuery.extend(true, options, key);
            } else {
                options[key] = value;
            }

            self._enable(); // Re-enable with newly set options

            return self;
        },

        /**
         * Bind events to DOM
         *
         * @this {Drawer}
         * @private
         */
        _enable: function () {
            var self, options;

            self = this;
            options = self.options;

            jQuery(document)
                .on('click', options.toggle, function (e) {
                    e.preventDefault();
                    self.toggle();
                })
                .on('click', '.' + options.classes.show + ' ' + options.content, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.hide();
                });
        },

        /**
         * Unbind events from DOM
         *
         * @this {Drawer}
         * @private
         */
        _disable: function () {
            var self, options;

            self = this;
            options = self.options;

            jQuery(document)
                .off('click', options.toggle)
                .off('click', options.content);
        },

        /**
         * Expand the drawer
         *
         * @this {Drawer}
         */
        show: function () {
            var self, options;

            self = this;
            options = self.options;

            jQuery(options.container).addClass(options.classes.show);
            jQuery(options.container).removeClass(options.classes.hide);
            jQuery(self.element).trigger('drawer.show');
        },

        /**
         * Collapse the drawer
         *
         * @this {Drawer}
         */
        hide: function () {
            var self, options;

            self = this;
            options = self.options;

            jQuery(options.container).addClass(options.classes.hide);
            jQuery(options.container).removeClass(options.classes.show);
            jQuery(self.element).trigger('drawer.hide');
        },

        /**
         * Toggle between the expanded and collapsed state of the drawer
         *
         * @this {Drawer}
         */
        toggle: function () {
            var self, options;

            self = this;
            options = self.options;

            if (jQuery(options.container).hasClass(options.classes.show)) {
                self.hide();
            } else {
                self.show();
            }
        }
    };

    $.fn.drawer = function (options) {
        return this.each(function () {
            if ($.data(this, 'drawer') === undefined) {
                $.data(this, 'drawer', new Lithe.Drawer(this, options));
            }
        });
    }

})(jQuery, window, document);
