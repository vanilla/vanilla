;(function ($, window, document, undefined) {
  'use strict';

  /**
   * Lithe Core JS library
   *
   * The {Lithe} object contains properties and functions required by the rest
   * of the Lithe Components.
   *
   * @author Kasper Isager <kasper@vanillaforums.com>
   */
  window.Lithe = window.Lithe || {
    /**
     * Classes for use with toggleable Components.
     *
     * Can be overridden anywhere at any time:
     *     Lithe.openClass = 'foo';
     */
    openClass   : 'is-open',
    closedClass : 'is-closed',
    activeClass : 'active',
    lastOpen    : $('is-open'),

    /**
     * Clear Lithe Components
     *
     * @this {Lithe}
     */
    clear: function () {
      // If a component was opened earlier, close it
      if (Lithe.lastOpen !== undefined) {
        Lithe.close(Lithe.lastOpen, Lithe.lastToggle);
      }
    },

    /**
     * Show ("Open") a Lithe component
     *
     * @this  {Lithe}
     * @param $el
     * @param $toggle
     */
    open: function ($el, $toggle, callback) {
      var self = this;

      // If a component was opened earlier, close it
      self.clear();

      self.lastOpen = $el;
      self.lastToggle = $toggle || undefined;

      $el.addClass(self.openClass).removeClass(self.closedClass);

      if ($toggle !== undefined) Lithe.activate($toggle);

      if (callback) {
        callback();
      }
      // Was a callback specified at an earlier instance? If so, execute it
      else if (self.lastOpen.data('openCallback')) {
        self.lastOpen.data('openCallback')();
      }

      // Clear any callback that might be stored
      self.lastOpen.data('openCallback', undefined);
    },

    /**
     * Hide ("Close") a Lithe component
     *
     * @param $el
     * @param $toggle
     */
    close: function ($el, $toggle, callback) {
      var self = this;

      $el.addClass(self.closedClass).removeClass(self.openClass);

      if ($toggle !== undefined) Lithe.deactivate($toggle);

      if (callback) {
        callback();
      }
      // Was a callback specified at an earlier instance? If so, execute it
      else if (self.lastOpen.data('closeCallback')) {
        self.lastOpen.data('closeCallback')();
      }

      // Clear any callback that might be stored
      self.lastOpen.data('closeCallback', undefined);
    },

    /**
     * Activate a Lithe component
     *
     * @param $el
     */
    activate: function ($el) {
      $el.addClass(this.activeClass);
    },

    /**
     * Deactivate a Lithe component
     *
     * @param $el
     */
    deactivate: function ($el) {
      $el.removeClass(this.activeClass);
    },

    /**
     * Toggle a Lithe component
     *
     * @param {Object}   $el
     * @param {Object}   $toggle
     * @param {Function} open
     * @param {Function} close
     */
    toggle: function ($el, $toggle, open, close) {
      var self = this;

      // If no element exists, don't go any further
      if (!$el.length) return;

      if ($el.hasClass(self.openClass)) {
        self.close($el, $toggle, close);
      } else {
        self.open($el, $toggle, open);
      }

      // Store callbacks so we can use them later.
      self.lastOpen.data('closeCallback', close);
      self.lastOpen.data('openCallback', open);
    }
  };

  // Clear Components on document click
  $(document).on('click', Lithe.clear);

}(jQuery, window, document));
