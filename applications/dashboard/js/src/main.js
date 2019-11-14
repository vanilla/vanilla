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
            '.radio-painted-wrapper input',
            '.exclude-icheck',
        ];

        var selector = 'input';

        ignores.forEach(function(element) {
            selector += ':not(' + element + ')';
        });

        $(selector, element).iCheck({
            aria: true
        }).on('ifChanged', function() {
            $(this).trigger('change');
            // Re-firing event for forward-compatibility.
            var evt = document.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            $(this)[0].dispatchEvent(evt);
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
        }
    });

    /**
     * Reset the panel javascript when the panel navigation is collapsed.
     */
    $(document).on('hidden.bs.collapse', function() {
        if ($('.main-container').hasClass('drawer-show')) {
            $('.js-drawer').trigger('drawer.show');
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
        var fullHeight = $(this).hasClass('js-full-height-modal')
        DashboardModal.activeModal = new DashboardModal($(this), { fullHeight: fullHeight });
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

    /**
     * Turn a toolbar with the .js-toolbar-sticky class into a sticky toolbar.
     *
     * This is an opt-in class because it may not work or be appropriate on all pages.
     */
    $(window).scroll(function () {
        var $toolbar = $('.js-toolbar-sticky');
        var cssClass = 'is-stuck';

        if ($(this).scrollTop() > $('header.navbar').height()) {
            $toolbar
                .addClass(cssClass)
                .outerWidth($('.main').outerWidth() - 2)
                .next('*')
                .css('margin-top', $toolbar.outerHeight());
        } else {
            $toolbar.removeClass(cssClass).outerWidth('').next('*').css('margin-top', '');
        }
    });
    $(window).resize(function () {
        var $toolbar = $('.js-toolbar-sticky.is-stuck');

        $toolbar.outerWidth($('.main').outerWidth() - 2);
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
