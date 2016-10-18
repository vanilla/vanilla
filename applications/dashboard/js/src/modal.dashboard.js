
var DashboardModal = (function() {

    var DashboardModal = function($trigger, settings) {
        this.id = Math.random().toString(36).substr(2, 9);
        this.setupTrigger($trigger);
        this.addToDom();

        this.settings = {};
        this.defaultContent.closeIcon = dashboardSymbol('close');
        $.extend(true, this.settings, this.defaultSettings, settings, $trigger.data());

        this.trigger = $trigger;
        this.target = $trigger.attr('href');
        this.addEventListeners();
        this.start();
    };

    DashboardModal.prototype = {

        activeModal: undefined,

        defaultSettings: {
            httpmethod: 'get',
            afterSuccess: function(json, sender) {},
            reloadPageOnSave: true
        },

        id: '',

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

        target: '',

        trigger: {},

        modalHtml: ' \
        <div><div class="modal-dialog {cssClass}" role="document"> \
            <div class="modal-content"> \
                <div class="modal-header js-modal-fixed"> \
                    <h4 class="modal-title">{title}</h4> \
                    <button type="button" class="btn-icon modal-close close" data-dismiss="modal" aria-label="Close"> \
                        {closeIcon} \
                    </button> \
                </div> \
                {form.open} \
                <div class="modal-body">{body}</div> \
                <div class="modal-footer js-modal-fixed">{footer}</div> \
                {form.close} \
            </div> \
        </div></div>',

        modalHtmlNoHeader: ' \
        <div><div class="modal-dialog modal-no-header {cssClass}" role="document"> \
            <div class="modal-content"> \
                <div class="modal-body">{body}</div> \
                <button type="button" class="btn-icon modal-close close" data-dismiss="modal" aria-label="Close"> \
                    {closeIcon} \
                </button> \
            </div> \
        </div></div>',

        modalShell: '<div class="modal fade" id="{id}" tabindex="-1" role="dialog" aria-hidden="true"></div>',

        start: function($trigger, settings) {
            $('#' + this.id).modal('show').focus();
            if (this.settings.modalType === 'confirm') {
                this.addConfirmContent();
            } else {
                this.addContent();
            }
        },

        load: function() {
            this.handleForm();
        },

        addToDom: function() {
            $('body').append(this.modalShell.replace('{id}', this.id));
        },

        setupTrigger: function($trigger) {
            $trigger.attr('data-target', '#' + this.id);
            $trigger.attr('data-modal-id', this.id);
        },

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
                    success: function(json) {
                        gdn.inform(json);
                        gdn.processTargets(json.Targets);
                        if (json.RedirectUrl) {
                            setTimeout(function() {
                                document.location.replace(json.RedirectUrl);
                            }, 300);
                        } else {
                            $('#' + self.id).modal('hide');
                            self.afterConfirmSuccess();
                        }
                    }
                });
            }
        },

        // Default is to remove the closest item with the class 'js-modal-item'
        afterConfirmSuccess: function() {
            var found = false;
            if (!this.settings.confirmaction || this.settings.confirmaction === 'delete') {
                found = this.trigger.closest('.js-modal-item').length !== 0;
                this.trigger.closest('.js-modal-item').remove();
            }

            // Refresh the page.
            if (!found) {
                document.location.replace(window.location.href);
            }
        },

        confirmContent: function() {
            // Replace language definitions
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

        addConfirmContent: function() {
            var self = this;
            $('#' + self.id).htmlTrigger(self.replaceHtml(self.confirmContent()));
        },

        replaceHtml: function(parsedContent) {

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

        addContent: function() {
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
                    $('#' + self.id).htmlTrigger(self.replaceHtml(content));
                }
            });
        },

        // Add any error messages to popup form or close modal on form save.
        handleForm: function(element) {
            var self = this;

            $('form', element).ajaxForm({
                data: {
                    'DeliveryType': 'VIEW',
                    'DeliveryMethod': 'JSON'
                },
                dataType: 'json',
                success: function(json, sender) {
                    gdn.inform(json);
                    gdn.processTargets(json.Targets);

                    if (json.FormSaved === true) {
                        self.afterFormSuccess(json, sender, json.RedirectUrl);
                        $('#' + self.id).modal('hide');
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

        // Respect redirectUrl after form saves and redirect.
        afterFormSuccess: function(json, sender, redirectUrl) {
            this.settings.afterSuccess(json, sender);
            if (redirectUrl) {
                setTimeout(function() {
                    document.location.replace(redirectUrl);
                }, 300);
            } else if (this.settings.reloadPageOnSave) {
                document.location.replace(window.location.href);
            }
        },

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
                $title.remove();
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
        }
    };

    return DashboardModal;

})();
