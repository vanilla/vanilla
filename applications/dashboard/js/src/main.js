/*!
 * Dashboard v3 - A new dashboard design for Vanilla.
 *
 * @author    Becky Van Bussel <beckyvanbussel@gmail.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 */

'use strict';

(function($) {

    var codeInput = {
        // Replaces any textarea with the 'js-code-input' class with an code editor.
        start: function(element) {
            $('.js-code-input', element).each(function () {
                codeInput.makeAceTextArea($(this));
            });
        },

        // Adds the 'js-code-input' class to a form and the mode and height data attributes.
        init: function(textarea, mode, height) {
            if (!textarea.length) {
                return;
            }
            textarea.addClass('js-code-input');
            textarea.data('code-input', {'mode': mode, 'height': height});
        },

        //
        makeAceTextArea: function (textarea) {
            var mode = textarea.data('code-input').mode;
            var height = textarea.data('code-input').height;
            var modes = ['html', 'css'];

            if (modes.indexOf(mode) === -1) {
                mode = 'html';
            }
            if (!height) {
                height = 400;
            }

            // Add the ace input before the actual textarea and hide the textarea.
            var formID = textarea.attr('id');
            textarea.before('<div id="editor-' + formID + '" style="height: ' + height + 'px;"></div>');
            textarea.hide();

            var editor = ace.edit('editor-' + formID);
            editor.$blockScrolling = Infinity;
            editor.getSession().setMode('ace/mode/' + mode);
            editor.setTheme('ace/theme/clouds');

            // Set the textarea value on the ace input and update the textarea when the ace input is updated.
            editor.getSession().setValue(textarea.val());
            editor.getSession().on('change', function () {
                textarea.val(editor.getSession().getValue());
            });
        }
    };

    function prettyPrintInit(element) {
        // Pretty print
        $('#Pockets td:nth-child(4)', element).each(function () {
            var html = $(this).html();
            $(this).html('<pre class="prettyprint lang-html" style="white-space: pre-wrap;">' + html + '</pre>');
        });
        $('pre', element).addClass('prettyprint lang-html');
        prettyPrint();
    }

    function aceInit(element) {
        // Editor classes
        codeInput.init($('.pockets #Form_Body', element), 'html', 200);
        // Don't let our code editor go taller than the window length. Makes for weird scrolling.
        codeInput.init($('#Form_CustomHtml', element), 'html', $(window).height() - 100);
        codeInput.init($('#Form_CustomCSS', element), 'css', $(window).height() - 100);
        codeInput.start(element);
    }


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

    function fluidFixedInit(element) {
        // margin-bottom on panel nav h4 is 9px, padding-bottom on .panel-left is 72px
        $('.js-fluid-fixed', element).fluidfixed({
            offsetBottom: 72 + 9
        });
    }

    function userDropDownInit(element) {
        var html = $('.js-dashboard-user-dropdown').html();
        if ($('.js-navbar .js-card-user', element).length !== 0) {
            new Drop({
                target: document.querySelector('.js-navbar .js-card-user', element),
                content: html,
                constrainToWindow: true,
                remove: true,
                tetherOptions: {
                    attachment: 'top right',
                    targetAttachment: 'bottom right',
                    offset: '-10 0'
                }
            });
        }
    }

    /**
     * Un-collapses a group if one of its links is active.
     *
     * @param element
     */
    function collapseInit(element) {
        var $active = $('.js-nav-collapsible a.active', element);
        var $collapsible = $active.parents('.collapse');
        $collapsible.addClass('in');
        $('a[href=#' + $collapsible.attr('id') + ']').attr('aria-expanded', 'true');
        $('a[href=#' + $collapsible.attr('id') + ']').removeClass('collapsed');
    }

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

    function icheckInit(element) {
        var selector = 'input:not(.label-selector-input):not(.toggle-input):not(.avatar-delete-input):not(.jcrop-keymgr)';

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

    function modalInit() {
        if (typeof(DashboardModal.activeModal) === 'object') {
            DashboardModal.activeModal.load();
        }
    }

    function responsiveTablesInit(element) {
        var containerSelector = '#main-row .main';

        // We're in a popup.
        if (typeof(DashboardModal.activeModal) === 'object') {
            containerSelector = '#' + DashboardModal.activeModal.id + ' .modal-body';
        }

        $('.js-tj', element).tablejenga({container: containerSelector});
    }

    function foggyInit(element) {
        var $foggy = $('.js-foggy', element);
        if ($foggy.data('isFoggy')) {
            $foggy.trigger('foggyOn');
        }
    }

    $(document).on('contentLoad', function(e) {
        prettyPrintInit(e.target); // prettifies <pre> blocks
        aceInit(e.target); // code editor
        collapseInit(e.target); // panel nav collapsing
        navbarHeightInit(e.target); // navbar height settings
        fluidFixedInit(e.target); // panel and scroll settings
        userDropDownInit(e.target); // navbar 'me' dropdown
        modalInit(); // modals (aka popups)
        clipboardInit(); // copy elements to the clipboard
        drawerInit(e.target); // responsive hamburger menu nav
        icheckInit(e.target); // checkboxes and radios
        expanderInit(e.target); // truncates text and adds link to expand
        responsiveTablesInit(e.target); // makes tables responsive
        foggyInit(e.target); // makes settings blurred out
    });

    /**
     * Adapted from http://stackoverflow.com/questions/4459379/preview-an-image-before-it-is-uploaded
     * Sets a image preview url for a uploaded files, not yet saved to the the server.
     */
    function readUrl(input) {
        if (input.files && input.files[0]) {
            var $preview = $(input).parents('.js-image-preview-form-group').find('.js-image-preview-new .js-image-preview');
            var reader = new FileReader();
            reader.onload = function (e) {
                $preview.attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Event handlers

    /**
     * Adds a preview of the uploaded, not-yet-saved image.
     */
    $(document).on('change', '.js-image-upload', function() {
        $(this).parents('.js-image-preview-form-group').find('.js-image-preview-new').removeClass('hidden');
        $(this).parents('.js-image-preview-form-group').find('.js-image-preview-old').addClass('hidden');
        readUrl(this);
    });

    /**
     * Removes the preview image and clears the file name from the input.
     */
    $(document).on('click', '.js-remove-image-preview', function(e) {
        e.preventDefault();
        var $parent = $(this).parents('.js-image-preview-form-group');
        $parent.find('.js-image-preview-old').removeClass('hidden');
        $parent.find('.js-image-preview-new').addClass('hidden').find('.js-image-preview').attr('src', '');
        var $input = $parent.find('.js-image-upload');
        var $inputFileName = $parent.find('.file-upload-choose');
        $input.val('');
        $inputFileName.html($inputFileName.data('placeholder'));
    });

    $(document).on('shown.bs.collapse', function() {
        if ($('.main-container').hasClass('drawer-show')) {
            $('.js-drawer').trigger('drawer.show');
        } else {
            $('.panel-nav .js-fluid-fixed').trigger('reset.FluidFixed');
        }
    });

    $(document).on('hidden.bs.collapse', function() {
        if ($('.main-container').hasClass('drawer-show')) {
            $('.js-drawer').trigger('drawer.show');
        } else {
            $('.panel-nav .js-fluid-fixed').trigger('reset.FluidFixed');
        }
    });

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

    $(document).on('change', '.js-file-upload', function() {
        var filename = $(this).val();
        if (filename.substring(3, 11) === 'fakepath') {
            filename = filename.substring(12);
        }
        if (filename) {
            $(this).parent().find('.file-upload-choose').html(filename);
        }
    });

    $(document).on('click', '.js-modal', function(e) {
        e.preventDefault();
        DashboardModal.activeModal = new DashboardModal($(this), {});
    });

    $(document).on('click', '.js-modal-confirm.js-hijack', function(e) {
        e.preventDefault();
        DashboardModal.activeModal = new DashboardModal($(this), {
            httpmethod: 'post',
            modalType: 'confirm'
        });
    });

    $(document).on('click', '.js-modal-confirm:not(.js-hijack)', function(e) {
        e.preventDefault();
        DashboardModal.activeModal = new DashboardModal($(this), {
            httpmethod: 'get',
            modalType: 'confirm',
            followLink: true // no ajax
        });
    });

    // Get new banner image.
    $(document).on('click', '.js-upload-email-image-button', function(e) {
        e.preventDefault();
        DashboardModal.activeModal = new DashboardModal($(this), {
            afterSuccess: emailStyles.reloadImage
        });
    });

    $(document).on('click', '.js-modal-close', function() {
        if (typeof(DashboardModal.activeModal) === 'object') {
            $('#' + DashboardModal.activeModal.id).modal('hide');
        }
    });

    $(document).on('foggyOn', function(e) {
        var $target = $(e.target);
        $target.attr('aria-hidden', 'true');
        $target.data('isFoggy', 'true');
        $target.addClass('foggy');

        // Make sure we mark already-disabled fields so as not to mistakenly mark them as enabled on foggyOff.
        $target.find(':input').each(function() {
            if ($(this).prop("disabled")) {
                $(this).data('foggy-disabled', 'true');
            } else {
                $(this).prop("disabled", true);
            }
        });
    });

    $(document).on('foggyOff', function(e) {
        var $target = $(e.target);
        $target.attr('aria-hidden', 'false');
        $target.data('isFoggy', 'false');
        $target.removeClass('foggy');

        // Be careful not to enable fields that should be disabled.
        $target.find(':input').each(function() {
            if (!$(this).data('foggy-disabled')) {
                $(this).prop("disabled", false);
            }
        });
    });

})(jQuery);

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
