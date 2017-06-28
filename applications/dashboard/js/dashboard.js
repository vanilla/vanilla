;(function ($, window, document, undefined) {
  'use strict';

  /**
   * Lithe Core JS library
   *
   * The {Lithe} object contains properties and functions required by the rest
   * of the Lithe components.
   *
   * @author Kasper Isager <kasper@vanillaforums.com>
   */
  window.Lithe = window.Lithe || {
    /**
     * Classes for use with toggleable components.
     *
     * Can be overridden anywhere at any time:
     *     Lithe.openClass = 'foo';
     */
    openClass   : 'is-open',
    closedClass : 'is-closed',
    activeClass : 'active',
    lastOpen    : $('is-open'),

    /**
     * Clear Lithe components
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

  // Clear components on document click
  $(document).on('click', Lithe.clear);

}(jQuery, window, document));

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

/**
 * This class handles the content parsing and rendering of two different types of modals:
 *
 * Regular modals that ajax-in the content from an endpoint.
 * Confirm modals that don't ajax-in the content.
 *
 */
var DashboardModal = (function() {

    /**
     * The settings we can configure when starting the DashboardModal.
     *
     * These can be added when initializing the DashboardModal via javascript,
     * or by using data attributes.
     *
     * @typedef {Object} DashboardModalSettings
     * @property {string} httpmethod The HTTP method for the confirm modal. Either 'get' or 'post'. 'post' will automatically add the TransientKey to the request.
     * @property {function} afterSuccess Function gets called on ajax success when handling a modal.
     * @property {boolean} reloadPageOnSave Whether to reload the page after the modal closes after ajax success. Default true.
     * @property {boolean} followLink Whether to follow a link when the confirm modal is accepted rather than simply closing the modal.
     * @property {string} cssClass A CSS class to add to the modal dialog.
     * @property {string} title The modal title.
     * @property {string} footer The modal footer.
     * @property {string} body The modal body.
     * @property {string} closeIcon The svg close icon from the dashboard symbol map.
     * @property {string} modalType The modal type. Defaults to 'regular'. Other options are 'confirm' or 'noheader'
     */

    /**
     * Initialize our dashboard modal.
     *
     * @param {jQuery} $trigger The element we clicked to trigger the modal.
     * @param {DashboardModalSettings} settings The modal settings.
     * @constructor
     */
    var DashboardModal = function($trigger, settings) {
        this.id = Math.random().toString(36).substr(2, 9);
        this.setupTrigger($trigger);
        this.addModalToDom();

        this.settings = {};
        this.defaultContent.closeIcon = dashboardSymbol('close');
        $.extend(true, this.settings, this.defaultSettings, settings, $trigger.data());

        this.trigger = $trigger;
        this.target = $trigger.attr('href');
        this.addEventListeners();
        this.start();
    };


    DashboardModal.prototype = {

        /**
         * The current active modal in the page.
         */
        activeModal: undefined,

        /**
         * The default settings.
         */
        defaultSettings: {
            httpmethod: 'get',
            afterSuccess: function(json, sender) {},
            reloadPageOnSave: true,
            modalType: 'regular'
        },

        /**
         * The generated id for the modal.
         */
        id: '',

        /**
         * The default content for the modal.
         */
        defaultContent: {
            cssClass: '',
            title: '',
            footer: '',
            body: '',
            closeIcon: '',
            form: {
                open: '',
                close: ''
            }
        },

        /**
         * The url to fetch the modal content from.
         */
        target: '',

        /**
         * The jQuery trigger object that is clicked to activate the modal.
         */
        trigger: {},

        /**
         * The default modal template for all modals.
         */
        modalHtml: ' \
        <div class="modal-dialog {cssClass}" role="document"> \
            <div class="modal-content"> \
                <div class="modal-header js-modal-fixed"> \
                    <h4 id="modalTitle" class="modal-title">{title}</h4> \
                    <button type="button" class="btn-icon modal-close close" data-dismiss="modal" aria-label="Close"> \
                        {closeIcon} \
                    </button> \
                </div> \
                {form.open} \
                <div class="modal-body">{body}</div> \
                <div class="modal-footer js-modal-fixed">{footer}</div> \
                {form.close} \
            </div> \
        </div>',

        /**
         * A modal with no separate header and footer.
         */
        modalHtmlNoHeader: ' \
        <div class="modal-dialog modal-no-header {cssClass}" role="document"> \
            <h4 id="modalTitle" class="modal-title hidden">{title}</h4> \
            <div class="modal-content"> \
                <div class="modal-body">{body}</div> \
                <button type="button" class="btn-icon modal-close close" data-dismiss="modal" aria-label="Close"> \
                    {closeIcon} \
                </button> \
            </div> \
        </div>',

        /**
         * The modal shell that we add to the DOM on initialization. Content eventually is added to the modal.
         */
        modalShell: '<div class="modal fade" id="{id}" tabindex="-1" role="dialog" aria-hidden="false" aria-labelledby="modalTitle"></div>',

        /**
         * Shows, gives focus to, and renders the modal.
         */
        start: function() {
            $('#' + this.id).modal('show').focus();
            if (this.settings.modalType === 'confirm') {
                this.renderConfirmModal();
            } else {
                this.renderModal();
            }
        },

        /**
         * Adds the modal to the DOM.
         */
        addModalToDom: function() {
            $('body').append(this.modalShell.replace('{id}', this.id));
        },

        /**
         * Adds the needed data attributes to the modal trigger.
         */
        setupTrigger: function($trigger) {
            $trigger.attr('data-target', '#' + this.id);
            $trigger.attr('data-modal-id', this.id);
        },

        /**
         * Adds event listeners to the modal.
         */
        addEventListeners: function() {
            var self = this;
            $('#' + self.id).on('shown.bs.modal', function() {
                self.handleForm($('#' + self.id));
            });
            $('#' + self.id).on('hidden.bs.modal', function() {
                $(this).remove();
            });
            $('#' + self.id).on('click', '.js-ok', function() {
                self.handleConfirm(this);
            });
            $('#' + self.id).on('click', '.js-cancel', function() {
                $('#' + self.id).modal('hide');
            });
        },

        /**
         * Gets the default content for a confirm modal.
         */
        getDefaultConfirmContent: function() {
            var confirmHeading = gdn.definition('ConfirmHeading', 'Confirm');
            var confirmText = gdn.definition('ConfirmText', 'Are you sure you want to do that?');
            var ok = gdn.definition('Okay', 'Okay');
            var cancel = gdn.definition('Cancel', 'Cancel');

            var footer = '<button class="btn btn-secondary btn-cancel js-cancel">' + cancel + '</button>';
            footer += '<button class="btn btn-primary btn-ok js-ok">' + ok + '</button>';

            return {
                title: confirmHeading,
                footer: footer,
                body: confirmText,
                cssClass: 'modal-sm modal-confirm'
            };
        },

        /**
         * Adds confirm content to the modal shell.
         */
        renderConfirmModal: function() {
            var self = this;
            $('#' + self.id).htmlTrigger(self.addContentToTemplate(self.getDefaultConfirmContent()));
        },

        /**
         * Makes an ajax call to the target and adds the page content to the modal shell.
         */
        renderModal: function() {
            var self = this;
            var ajaxData = {
                'DeliveryType' : 'VIEW',
                'DeliveryMethod' : 'JSON'
            };

            $.ajax({
                method: 'GET',
                url: self.target,
                data: ajaxData,
                dataType: 'json',
                error: function(xhr) {
                    gdn.informError(xhr);
                    $('#' + self.id).modal('hide');
                },
                success: function(json) {
                    var body = json.Data;
                    var content = self.parseBody(body);
                    $('#' + self.id).htmlTrigger(self.addContentToTemplate(content));
                }
            });
        },

        /**
         * Replaces curly-braced variables in the templates with the parsedContent.
         */
        addContentToTemplate: function(parsedContent) {

            // Copy the defaults into the content array
            var content = {};
            $.extend(true, content, this.defaultContent);

            // Data attributes override parsed content, content overrides defaults.
            $.extend(true, parsedContent, this.settings);
            $.extend(true, content, parsedContent);

            var html = '';

            if (this.settings.modalType === 'noheader') {
                html = this.modalHtmlNoHeader;
            } else {
                html = this.modalHtml;
            }

            html = html.replace('{body}', content.body);
            html = html.replace('{cssClass}', content.cssClass);
            html = html.replace('{title}', content.title);
            html = html.replace('{closeIcon}', content.closeIcon);
            html = html.replace('{footer}', content.footer);
            html = html.replace('{form.open}', content.form.open);
            html = html.replace('{form.close}', content.form.close);

            return html;
        },


        /**
         * Parses a page to find the title, footer, form and body elements.
         *
         * If there's a form in the page, removes the opening and closing form tags.
         * These get readded later, wrapping around the content and footer.
         *
         * The title is the page's h1 element, the footer is the contents of the first `.Buttons`,
         * `.form-footer` or `.js-modal-footer` element, if one exists on the page.
         */
        parseBody: function(body) {
            var title = '';
            var footer = '';
            var formTag = '';
            var formCloseTag = '';
            var $elem = $('<div />').append($($.parseHTML(body + ''))); // Typecast html to a string and create a DOM node
            var $title = $elem.find('h1');
            var $footer = $elem.find('.Buttons, .form-footer, .js-modal-footer');
            var $form = $elem.find('form');

            // Pull out the H1 block from the view to add to the modal title
            if (this.settings.modalType !== 'noheader' && $title.length !== 0) {
                title = $title.html();
                if ($elem.find('.header-block').length !== 0) {
                    $elem.find('.header-block').remove();
                } else {
                    $title.remove();
                }
                body = $elem.html();
            }

            // Pull out the buttons from the view to add to the modal footer
            if ($footer.length !== 0) {
                footer = $footer.html();
                $footer.remove();
                body = $elem.html();
            }

            // Pull out the form opening and closing tags to wrap around the modal-content and modal-footer
            if ($form.length !== 0) {
                formCloseTag = '</form>';
                var formHtml = $form.prop('outerHTML');
                formTag = formHtml.split('>')[0] += '>';
                body = body.replace(formTag, '');
                body = body.replace('</form>', '');
            }

            return {
                title: title,
                footer: footer,
                body: body,
                form: {
                    open: formTag,
                    close: formCloseTag
                }
            };
        },

        /**
         * Handles the submitting of and the response of a form in a modal.
         */
        handleForm: function(element) {
            var self = this;

            $('form', element).ajaxForm({
                data: {
                    'DeliveryType': 'VIEW',
                    'DeliveryMethod': 'JSON'
                },
                dataType: 'json',
                success: function(json, sender) {
                    self.settings.afterSuccess(json, sender);
                    gdn.inform(json);
                    gdn.processTargets(json.Targets);

                    if (json.FormSaved === true) {
                        self.handleSuccess(json);
                    } else {
                        var body = json.Data;
                        var content = self.parseBody(body);
                        $('#' + self.id + ' .modal-body').htmlTrigger(content.body);
                        $('#' + self.id + ' .modal-body').scrollTop(0);
                    }
                },
                error: function(xhr) {
                    gdn.informError(xhr);
                    $('#' + self.id).modal('hide');
                }
            });
        },

        /**
         * Handles the submitting of and the response of a form in a modal.
         */
        handleConfirm: function() {
            var self = this;

            // Refresh the page.
            if (self.settings.followLink) {
                document.location.replace(self.target);
            } else {
                // request the target via ajax
                var ajaxData = {'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON'};
                if (self.settings.httpmethod === 'post') {
                    ajaxData.TransientKey = gdn.definition('TransientKey');
                }

                $.ajax({
                    method: (self.settings.httpmethod === 'post') ? 'POST' : 'GET',
                    url: self.target,
                    data: ajaxData,
                    dataType: 'json',
                    error: function(xhr) {
                        gdn.informError(xhr);
                        $('#' + self.id).modal('hide');
                    },
                    success: function(json, sender) {
                        self.settings.afterSuccess(json, sender);
                        gdn.inform(json);
                        gdn.processTargets(json.Targets);
                        self.handleSuccess(json);
                    }
                });
            }
        },

        /**
         * Handles the ajax success. If there's a RedirectUrl set, then redirect. Reload the page if there
         * are no Targets set AND reloadPageOnSave is true.
         *
         * @param json
         */
        handleSuccess: function(json) {
            if (json.RedirectTo) {
                setTimeout(function() {
                    document.location.replace(json.RedirectTo);
                }, 300);
            } else {
                $('#' + this.id).modal('hide');

                // We'll only reload if there are no targets set. If there are targets set, we can
                // assume that the page doesn't need to be reloaded, since we'll ajax remove/edit
                // the page.
                if (this.settings.reloadPageOnSave && (json.Targets.length === 0)) {
                    document.location.replace(window.location.href);
                }
            }
        }
    };

    return DashboardModal;

})();

$(document).on('contentLoad', function(e) {
    // Reveals spoiler
    $(e.target).on('click', '.spoiler-trigger', function(e) {
        e.stopPropagation();
        e.preventDefault();
        $(this).closest('.spoiler')
            .addClass('spoiler-visible')
            .find('.form-control').focus().select(); // Select text field if it's text
    });
});

/*!
 * Dashboard v3 - A new dashboard design for Vanilla.
 *
 * @author    Becky Van Bussel <beckyvanbussel@gmail.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 */

'use strict';

(function($) {

    /**
     * This uses the ace vendor component to wire up our code editors. We currently use the code editor
     * in the Custom CSS plugin and in the Pockets plugin.
     *
     * Selector: `.js-code-input`
     */
    var codeInput = {
        // Replaces any textarea with the 'js-code-input' class with an code editor.
        start: function(element) {
            $('.js-code-input', element).each(function () {
                codeInput.makeAceTextArea($(this));
            });
        },

        // Adds the 'js-code-input' class to a form and the mode and height data attributes.
        init: function($textarea, mode, height) {
            if (!$textarea.length) {
                return;
            }
            $textarea.addClass('js-code-input');
            $textarea.data('code-input', {'mode': mode, 'height': height});
        },

        makeAceTextArea: function ($textarea) {
            var mode = $textarea.data('code-input').mode;
            var height = $textarea.data('code-input').height;
            var modes = ['html', 'css'];

            if (modes.indexOf(mode) === -1) {
                mode = 'html';
            }
            if (!height) {
                height = 400;
            }

            // Add the ace input before the actual textarea and hide the textarea.
            var formID = $textarea.attr('id');
            $textarea.before('<div id="editor-' + formID + '" style="height: ' + height + 'px;"></div>');
            $textarea.hide();

            var editor = ace.edit('editor-' + formID);
            editor.$blockScrolling = Infinity;
            editor.getSession().setMode('ace/mode/' + mode);
            editor.getSession().setUseWorker(false);
            editor.setTheme('ace/theme/clouds');

            // Set the textarea value on the ace input and update the textarea when the ace input is updated.
            editor.getSession().setValue($textarea.val());
            editor.getSession().on('change', function () {
                $textarea.val(editor.getSession().getValue());
            });
        }
    };

    /**
     * Uses the handy codeInput.init function to add the appropriate data and classes to elements that should
     * be rich text editors. You can initialize elements here or simply add the `js-code-input` CSS class and
     * the appropriate data attributes to the textarea markup.
     *
     * @param element - The scope of the function.
     */
    function aceInit(element) {
        // Editor classes
        codeInput.init($('.js-pocket-body', element), 'html', 300);

        // Don't let our code editor go taller than the window length. Makes for weird scrolling.
        codeInput.init($('#Form_CustomHtml', element), 'html', $(window).height() - 100);
        codeInput.init($('#Form_CustomCSS', element), 'css', $(window).height() - 100);
        codeInput.start(element);
    }

    /**
     * Styles and adds syntax hilighting to code blocks.
     *
     * @param element - The scope of the function.
     */
    function prettyPrintInit(element) {
        $('#Pockets td:nth-child(4)', element).each(function () {
            var html = $(this).html();
            $(this).html('<pre class="prettyprint lang-html" style="white-space: pre-wrap;">' + html + '</pre>');
        });
        $('pre', element).addClass('prettyprint lang-html');
        prettyPrint();
    }

    /**
     * Add a CSS class to the navbar based on it scroll position.
     *
     * @param element - The scope of the function.
     */
    function navbarHeightInit(element) {
        var $navbar = $('.js-navbar', element);

        $navbar.addClass('navbar-short');
        var navShortHeight = $navbar.outerHeight(true);
        $navbar.removeClass('navbar-short');
        var navHeight = $navbar.outerHeight(true);
        var navOffset = navHeight - navShortHeight;

        // If we load in the middle of the page, we should have a short navbar.
        if ($(window).scrollTop() > navOffset) {
            $navbar.addClass('navbar-short');
        }

        $(window).on('scroll', function() {
            if ($(window).scrollTop() > navOffset) {
                $navbar.addClass('navbar-short');
            } else {
                $navbar.removeClass('navbar-short');
            }
        });
    }

    /**
     * Start fluidfixed on the dashboard panel navigation.
     *
     * @param element - The scope of the function.
     */
    function fluidFixedInit(element) {
        // margin-bottom on panel nav h4 is 9px, padding-bottom on .panel-left is 72px
        $('.js-fluid-fixed', element).fluidfixed({
            offsetBottom: 72 + 9
        });
    }

    /**
     * Initialize drop.js on any element with the class 'js-drop'. The element must have their id attribute set and
     * must specify the html content it will reveal when it is clicked.
     *
     * Selector: `.js-drop`
     * Attribute: `data-content-id="id_of_element"`
     *
     * @param element - The scope of the function.
     */
    function dropInit(element) {
        $('.js-drop', element).each(function() {
            var $trigger = $(this);
            var contentSelector = $trigger.data('contentId');
            var triggerSelector = $trigger.attr('id');
            var html = $('#' + contentSelector).html();

            if (triggerSelector === undefined) {
                console.error('Drop trigger must be unique and have an id attribute set.');
                return;
            }

            if (html === undefined) {
                console.error('The drop content needs to be configured properly with the correct id attribute.');
                return;
            }

            new Drop({
                target: document.querySelector('#' + triggerSelector),
                content: html,
                constrainToWindow: true,
                remove: true,
                tetherOptions: {
                    attachment: 'top right',
                    targetAttachment: 'bottom right',
                    offset: '-10 0'
                }
            }).on('open', function() {
                $(this.content).trigger('contentLoad');
            });
        });
    }

    /**
     * Un-collapses a group if one of its links is active. Note that the functionality for the collapse
     * javascript is contained in ../vendors/bootstrap/collapse.js
     *
     * @param element - The scope of the function.
     */
    function collapseInit(element) {
        var $active = $('.js-nav-collapsible a.active', element);
        var $collapsible = $active.parents('.collapse');
        $collapsible.addClass('in');
        $('a[href=#' + $collapsible.attr('id') + ']').attr('aria-expanded', 'true');
        $('a[href=#' + $collapsible.attr('id') + ']').removeClass('collapsed');
    }

    /**
     * Copies the text from an element to the clipboard. Displays a tooltip on success. Set the
     * clipboardTarget data attribute to indicate the text that should be copied. Set the successText
     * attribute to the message to display on success.
     *
     * Selector: `.btn-copy`
     * Attributes: `data-clipboard-target="#text_to_copy"`
     *             `data-success-text="Copied!"`
     */
    function clipboardInit() {
        var clipboard = new Clipboard('.btn-copy');

        clipboard.on('success', function(e) {
            var tooltip = $(e.trigger).tooltip({
                show: true,
                placement: 'bottom',
                title: $(e.trigger).attr('data-success-text'),
                trigger: 'manual',
            });
            tooltip.tooltip('show');
            setTimeout(function() {
                tooltip.tooltip('hide');
            }, '2000');
        });

        clipboard.on('error', function(e) {
            console.log(e);
        });
    }

    /**
     * This handles the drawer/hamburger menu functionality of the panel navigation on small screen sizes.
     *
     * @param element - The scope of the function.
     */
    function drawerInit(element) {

        // Selectors
        var drawer = '.js-drawer';
        var drawerToggle = '.js-drawer-toggle';
        var content = '.main-row .main';
        var container = '.main-container';

        $(drawer, element).drawer({
            toggle: drawerToggle,
            container: container,
            content: content
        });

        $(drawerToggle).on('click', function() {
            window.scrollTo(0, 0);
        });

        $(drawer, element).on('drawer.show', function() {
            $('.panel-nav .js-fluid-fixed', element).trigger('detach.FluidFixed');
            $(content, element).height($('.panel-nav .js-fluid-fixed', element).outerHeight(true) + 132);
            $(content, element).css('overflow', 'hidden');

        });

        $(drawer, element).on('drawer.hide', function() {
            // TODO: We should only reset if the panel is actually displayed.
            $('.panel-nav .js-fluid-fixed', element).trigger('reset.FluidFixed');
            $(content, element).height('auto');
            $(content, element).css('overflow', 'inherit');
        });

        $(window).resize(function() {
            if ($(drawerToggle, element).css('display') !== 'none') {
                $(container, element).addClass('drawer-hide');
                $(container, element).removeClass('drawer-show');
                $(drawer, element).trigger('drawer.hide');
            }
        });
    }

    /**
     * Transforms all checkboxes or radios (with the exception of those in the ignore list)
     * into style-able checkboxes and radios.
     *
     * @param element - The scope of the function.
     */
    function icheckInit(element) {
        var ignores = [
            '.label-selector-input',
            '.toggle-input',
            '.avatar-delete-input',
            '.jcrop-keymgr',
            '.checkbox-painted-wrapper input',
            '.radio-painted-wrapper input'
        ];

        var selector = 'input';

        ignores.forEach(function(element) {
            selector += ':not(' + element + ')';
        });

        $(selector, element).iCheck({
            aria: true
        }).on('ifChanged', function() {
            $(this).trigger('change');
        });

        $(selector, element).on('inputChecked', function() {
            $(this).iCheck('check');
        });
        $(selector, element).on('inputDisabled', function() {
            $(this).iCheck('disable');
        });
    }

    /**
     * Starts expander functionality (aka "show more") for feed descriptions on the homepage and
     * for toaster messages.
     *
     * @param element - The scope of the function.
     */
    function expanderInit(element) {
        $('.FeedDescription', element).expander({
            slicePoint: 65,
            normalizeWhitespace: true,
            expandText: gdn.definition('ExpandText', 'more'),
            userCollapseText: gdn.definition('CollapseText', 'less')
        });

        $('.InformMessageBody, .toaster-body', element).expander({
            slicePoint: 60,
            normalizeWhitespace: true,
            expandText: gdn.definition('ExpandText', 'more'),
            userCollapseText: gdn.definition('', '')
        });
    }

    /**
     * Shows any active modal. This is needed for form errors.
     */
    function modalInit() {
        if (typeof(DashboardModal.activeModal) === 'object') {
            DashboardModal.activeModal.handleForm();
        }
    }

    /**
     * Starts tablejenga on elements with the `.js-tj` class.
     *
     * Selector: `.js-tj`
     *
     * @param element - The scope of the function.
     */
    function responsiveTablesInit(element) {
        var containerSelector = '#main-row .main';

        // We're in a popup.
        if (typeof(DashboardModal.activeModal) === 'object') {
            containerSelector = '#' + DashboardModal.activeModal.id + ' .modal-body';
        }

        $('.js-tj', element).tablejenga({container: containerSelector});
    }

    /**
     * Starts the foggy functionality.
     *
     * Selector: `.js-foggy`
     * Attribute: `data-is-foggy={true|false}`
     *
     * @param element - The scope of the function.
     */
    function foggyInit(element) {
        var $foggy = $('.js-foggy', element);
        if ($foggy.data('isFoggy')) {
            $foggy.trigger('foggyOn');
        }
    }

    /**
     * Initializes the check-all jquery plugin. Adds 'select all' functionality to checkboxes.
     * The trigger must have a `js-check-all` css class applied to it. It manages input checkboxes
     * with the `js-check-me` css class applied.
     *
     * Selectors: `.js-check-all` for the "Check all" checkbox.
     *            `.js-check-me` for the child checkboxes.
     *
     * @param element - The scope of the function.
     */
    function checkallInit(element) {
        $('.js-check-all', element).checkall({
            target: '.js-check-me'
        });
    }

    /**
     * Makes sure our dropdowns don't extend past the document height by making the dropdown drop up
     * if it gets too close to the bottom of the page. Note that the actual dropdown javascript
     * functionality is contained in ../vendors/bootstrap/dropdown.js This function just changes whether the
     * dropdown opens up or opens down.
     *
     * Selector: `.dropdown`
     *
     * @param element - The scope of the function.
     */
    function dropDownInit(element) {
        $('.dropdown', element).each(function() {
            var $dropdown = $(this);
            var offset = $dropdown.offset();
            var menuHeight = $('.dropdown-menu', $dropdown).height();
            var toggleHeight = $('.dropdown-toggle', $dropdown).height();
            var documentHeight = $(document).height();
            var padding = 6;

            if (menuHeight + toggleHeight + offset.top + padding >= documentHeight) {
                $dropdown.addClass('dropup');
            }
        });
    }

    /**
     * If a btn-group gets too long for the window width, this will transform it into a dropdown-filter.
     *
     * Selector: `.btn-group`
     *
     * @param element - The scope of the function.
     */
    function buttonGroupInit(element) {
        buttonGroup(element);
    }

    /**
     * Run through all our javascript functionality and start everything up.
     */
    $(document).on('contentLoad', function(e) {
        prettyPrintInit(e.target); // prettifies <pre> blocks
        aceInit(e.target); // code editor
        collapseInit(e.target); // panel nav collapsing
        navbarHeightInit(e.target); // navbar height settings
        fluidFixedInit(e.target); // panel and scroll settings
        dropInit(e.target); // navbar 'me' dropdown
        modalInit(); // modals (aka popups)
        clipboardInit(); // copy elements to the clipboard
        drawerInit(e.target); // responsive hamburger menu nav
        icheckInit(e.target); // checkboxes and radios
        expanderInit(e.target); // truncates text and adds link to expand
        responsiveTablesInit(e.target); // makes tables responsive
        foggyInit(e.target); // makes settings blurred out
        checkallInit(e.target); // handles 'select all' type checkboxes
        dropDownInit(e.target); // makes sure our dropdowns open in the right direction
        buttonGroupInit(e.target); // changes button groups that get too long into selects
    });

    /**
     * Adapted from http://stackoverflow.com/questions/4459379/preview-an-image-before-it-is-uploaded
     * Sets a image preview url for a uploaded files, not yet saved to the the server.
     * There's a rendering function for this in Gdn_Form: `imageUploadPreview()`.
     * You'll probably want to use it to generate the markup for this.
     *
     * Selectors: `.js-image-preview`
     *            `.js-image-preview-new`
     *            `.js-image-preview-form-group`
     */
    function readUrl(input) {
        if (input.files && input.files[0]) {
            var $preview = $(input).parents('.js-image-preview-form-group').find('.js-image-preview-new .js-image-preview');
            var reader = new FileReader();
            reader.onload = function (e) {
                if (e.target.result.startsWith('data:image')) {
                    $preview.attr('src', e.target.result);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Event handlers

    /**
     * Adds a preview of the uploaded, not-yet-saved image.
     * There's a rendering function for this in Gdn_Form: `imageUploadPreview()`.
     * You'll probably want to use it to generate the markup for this.
     *
     * Selectors: `.js-image-upload`
     *            `.js-image-preview-old`
     *            `.js-image-preview-new`
     *            `.js-image-preview-form-group`
     */
    $(document).on('change', '.js-image-upload', function() {
        $(this).parents('.js-image-preview-form-group').find('.js-image-preview-new').removeClass('hidden');
        $(this).parents('.js-image-preview-form-group').find('.js-image-preview-old').addClass('hidden');
        readUrl(this);
    });

    /**
     * Removes the preview image and clears the file name from the input.
     * There's a rendering function for this in Gdn_Form: `imageUploadPreview()`.
     * You'll probably want to use it to generate the markup for this.
     *
     * Selectors: `.js-remove-image-preview`
     *            `.js-image-preview-old`
     *            `.js-image-preview-new`
     *            `.js-image-preview`
     *            `.js-image-upload`
     *            `.js-image-preview-form-group`
     */
    $(document).on('click', '.js-remove-image-preview', function(e) {
        e.preventDefault();
        var $parent = $(this).parents('.js-image-preview-form-group');
        $parent.find('.js-image-preview-old').removeClass('hidden');
        $parent.find('.js-image-preview-new').addClass('hidden').find('.js-image-preview').attr('src', '');
        var $input = $parent.find('.js-image-upload');
        var $inputFileName = $parent.find('.file-upload-choose');
        $input.val('');
        $input.removeAttr('value');
        $inputFileName.html($inputFileName.data('placeholder'));
    });

    /**
     * Reset the panel javascript when the panel navigation is expanded.
     */
    $(document).on('shown.bs.collapse', function() {
        if ($('.main-container').hasClass('drawer-show')) {
            $('.js-drawer').trigger('drawer.show');
        } else {
            $('.panel-nav .js-fluid-fixed').trigger('reset.FluidFixed');
        }
    });

    /**
     * Reset the panel javascript when the panel navigation is collapsed.
     */
    $(document).on('hidden.bs.collapse', function() {
        if ($('.main-container').hasClass('drawer-show')) {
            $('.js-drawer').trigger('drawer.show');
        } else {
            $('.panel-nav .js-fluid-fixed').trigger('reset.FluidFixed');
        }
    });

    /**
     * File Upload filename preview.
     * There's a rendering function for this in Gdn_Form: `fileUpload()`.
     * You'll probably want to use it to generate the markup for this.
     *
     * Selector: `.js-file-upload`
     */
    $(document).on('change', '.js-file-upload', function() {
        var filename = $(this).val();
        if (filename.substring(3, 11) === 'fakepath') {
            filename = filename.substring(12);
        }
        if (filename) {
            $(this).parent().find('.file-upload-choose').html(filename);
        }
    });

    // Modal handling

    /**
     * Start regular modal.
     *
     * Selector: `.js-modal`
     */
    $(document).on('click', '.js-modal', function(e) {
        e.preventDefault();
        DashboardModal.activeModal = new DashboardModal($(this), {});
    });

    /**
     * Start confirm modal.
     *
     * Selector: `.js-modal-confirm`
     * Attribute: `data-follow-link:true` - Follows the link on confirm, otherwise stays on the page.
     */
    $(document).on('click', '.js-modal-confirm', function(e) {
        e.preventDefault();
        var followLink = $(this).data('followLink') === 'true';

        DashboardModal.activeModal = new DashboardModal($(this), {
            httpmethod: 'post',
            modalType: 'confirm',
            followLink: followLink // no ajax
        });
    });

    /**
     * Close active modal.
     *
     * Selector: `.js-modal-close`
     */
    $(document).on('click', '.js-modal-close', function() {
        if (typeof(DashboardModal.activeModal) === 'object') {
            $('#' + DashboardModal.activeModal.id).modal('hide');
        }
    });

    // Foggy handling

    /**
     * Disables inputs and adds a foggy CSS class to the target to make the target look foggy.
     */
    $(document).on('foggyOn', function(e) {
        var $target = $(e.target);
        $target.attr('aria-hidden', 'true');
        $target.data('isFoggy', 'true');
        $target.addClass('foggy');

        // Make sure we mark already-disabled fields so as not to mistakenly mark them as enabled on foggyOff.
        $target.find(':input').each(function() {
            if ($(this).prop('disabled')) {
                $(this).data('foggy-disabled', 'true');
            } else {
                $(this).prop('disabled', true);
            }
        });
    });

    /**
     * Enables inputs and removes the foggy CSS class.
     */
    $(document).on('foggyOff', function(e) {
        var $target = $(e.target);
        $target.attr('aria-hidden', 'false');
        $target.data('isFoggy', 'false');
        $target.removeClass('foggy');

        // Be careful not to enable fields that should be disabled.
        $target.find(':input').each(function() {
            if (!$(this).data('foggy-disabled')) {
                $(this).prop('disabled', false);
            }
        });
    });

    // Navigation preferences saving

    /**
     * Saves the panel navigation collapse preferences.
     *
     * Selector: `.js-save-pref-collapse`
     */
    $(document).on('click', '.js-save-pref-collapse', function() {
        var key = $(this).data('key');
        var collapsed = !$(this).hasClass('collapsed');

        // request the target via ajax
        var ajaxData = {'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON'};

        ajaxData.TransientKey = gdn.definition('TransientKey');
        ajaxData.key = key;
        ajaxData.collapsed = collapsed;

        $.ajax({
            method: 'POST',
            url: gdn.url('dashboard/userpreferencecollapse'),
            data: ajaxData,
            dataType: 'json'
        });
    });

    /**
     * Saves the preference for the landing page for a top-level section.
     *
     * Selector: `.js-save-pref-section-landing-page`
     * Attributes: `data-link-path="/path/to/settingspage"`
     *             `data-section="Moderation"`
     */
    $(document).on('click', '.js-save-pref-section-landing-page', function() {
        var url = $(this).data('linkPath');
        var section = $(this).data('section');

        // request the target via ajax
        var ajaxData = {'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON'};

        ajaxData.TransientKey = gdn.definition('TransientKey');
        ajaxData.url = url;
        ajaxData.section = section;

        $.ajax({
            method: 'POST',
            url: gdn.url('dashboard/userpreferencesectionlandingpage'),
            data: ajaxData,
            dataType: 'json'
        });
    });

    /**
     * Saves the preference for the dashboard landing page.
     *
     * Selector: `.js-save-pref-dashboard-landing-page`
     * Attribute: `data-section="Moderation"`
     */
    $(document).on('click', '.js-save-pref-dashboard-landing-page', function() {
        var section = $(this).data('section');

        // request the target via ajax
        var ajaxData = {'DeliveryType' : 'VIEW', 'DeliveryMethod' : 'JSON'};

        ajaxData.TransientKey = gdn.definition('TransientKey');
        ajaxData.section = section;

        $.ajax({
            method: 'POST',
            url: gdn.url('dashboard/userpreferencedashboardlandingpage'),
            data: ajaxData,
            dataType: 'json'
        });
    });
})(jQuery);

/**
 * Returns an HTML string to render a svg icon.
 *
 * @param {string} name - The icon name.
 * @param {string} alt - The alt text for the icon.
 * @param {string} cssClass - The css class to apply to the svg.
 * @returns {string} The HTML for the svg icon.
 */
var dashboardSymbol =  function(name, alt, cssClass) {
    if (alt) {
        alt = 'alt="' + alt + '" ';
    } else {
        alt = '';
    }

    if (!cssClass) {
        cssClass = '';
    }

    return '<svg ' + alt + ' class="icon ' + cssClass + 'icon-svg-' + name + '" viewBox="0 0 17 17"><use xlink:href=\"#' + name + '" /></svg>';
};
