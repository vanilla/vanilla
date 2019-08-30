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
        addModalToDom: function () {
            var newModalContainer = document.getElementById("modals");
            // Make sure that we insert our modals before
            var modal = document.createElement("div");
            document.body.insertBefore(modal, newModalContainer);
            modal.outerHTML = this.modalShell.replace('{id}', this.id);
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

            let cssClass = this.settings.fullHeight ? content.cssClass + " modal-full-height" : content.cssClass;

            html = html.replace('{body}', content.body);
            html = html.replace('{cssClass}', cssClass);
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
