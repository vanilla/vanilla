jQuery(document).ready(function($) {

    // Ajax-test addons before enabling
    $('a.EnableAddon').click(function(e) {
        e.preventDefault();
        gdn.clearAddonErrors();

        var url = $(this).attr('href');
        var urlParts = url.split('/');
        var addonType = urlParts[urlParts.length - 4];

        switch (addonType) {
            case 'plugins':
                addonType = 'Plugin';
                break;
            case 'applications':
                addonType = 'Application';
                break;
            case 'themes':
                addonType = 'Theme';
                break;
            case 'locales':
                addonType = 'Locale';
                break;
        }

        if ($(this).hasClass('EnableTheme'))
            addonType = 'Theme';

        if (addonType != 'Theme') {
            $('.TinyProgress').remove();
            $(this).after('<span class="TinyProgress">&#160;</span>');
        }
        var addonName = urlParts[urlParts.length - 2];
        var testUrl = gdn.url('/dashboard/settings/testaddon/' + addonType + '/' + addonName + '/' + gdn.definition('TransientKey'));

        $.ajax({
            type: "GET",
            url: testUrl,
            data: {'DeliveryType': 'VIEW'},
            dataType: 'html',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                // Remove any old errors from the form
                gdn.fillAddonErrors(XMLHttpRequest.responseText);
            },
            success: function(data) {
                if (data != 'Success') {
                    gdn.fillAddonErrors(data);
                } else {
                    // If not mobile themes, traditional submit.
                    // if (url.toLowerCase().indexOf('mobilethemes') == -1) {
                        document.location = url;
                    // } else {
                    //     // Start progress
                    //     var $currentThemeBlock = $(e.target).closest('.themeblock');
                    //     $currentThemeBlock.addClass('theme-progressing');
                    //     $currentThemeBlock.find('.theme-apply-progress').addClass('TinyProgress');
                    //
                    //     $.ajax({
                    //         type: 'GET',
                    //         url: url,
                    //         data: {'DeliveryType': 'VIEW'},
                    //         dataType: 'html',
                    //         error: function(XMLHttpRequest, textStatus, errorThrown) {
                    //             // Remove any old errors from the form
                    //             gdn.fillAddonErrors(XMLHttpRequest.responseText);
                    //         },
                    //         success: function(data) {
                    //             if (data != 'Success') {
                    //                 gdn.fillAddonErrors(data);
                    //             } else {
                    //                 gdn.setMobileTheme(e);
                    //             }
                    //         }
                    //     });
                    // }
                }
            }
        });
        return true;
    });

    /**
     * This will strip any current-theme classes from the themeblocks and apply
     * it to the latest successfully activated mobile theme.
     *
     * @param Event e
     */
    // gdn.setMobileTheme = function(e) {
    //     var $currentThemeBlock = $(e.target).closest('.themeblock');
    //     var $themeblocks = $('.themeblock');
    //
    //     $themeblocks.each(function(i, el) {
    //         $(el).removeClass('current-theme');
    //         $(el).removeClass('theme-progressing');
    //         $(el).find('.theme-apply-progress').removeClass('TinyProgress');
    //     });
    //
    //     $currentThemeBlock.addClass('current-theme');
    // };

    gdn.clearAddonErrors = function() {
        $('div.TestAddonErrors:not(.Hidden)').remove();
        $('.TinyProgress').remove();
    };

    gdn.fillAddonErrors = function(errorMessage) {
        $('.TinyProgress').remove();
        err = $('div.TestAddonErrors');
        html = $(err).html();
        html = html.replace('%s', errorMessage);
        $(err).before('<div class="Messages Errors TestAddonErrors">' + html + '</div>');
        $('div.TestAddonErrors:first').removeClass('Hidden');
        // $(window).scrollTop($("div.TestAddonErrors").offset().top);
        $(window).scrollTop();
    };

    // Ajax-test addons before enabling
    $('.js-preview-addon').click(function() {
        gdn.clearAddonErrors();

        var url = $(this).attr('href');
        var urlParts = url.split('/');
        var addonName = urlParts[urlParts.length - 1];
        var testUrl = gdn.url('/dashboard/settings/testaddon/Theme/' + addonName + '/' + gdn.definition('TransientKey'));
        var url = url + '/' + gdn.definition('TransientKey');

        $.ajax({
            type: "GET",
            url: testUrl,
            data: {'DeliveryType': 'VIEW'},
            dataType: 'html',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                // Remove any old errors from the form
                gdn.fillAddonErrors(XMLHttpRequest.responseText);
            },
            success: function(data) {
                if (data != 'Success') {
                    gdn.fillAddonErrors(data);
                } else {
                    document.location = url;
                }
            }
        });
        return false;
    });

    // Selection for theme styles.
    $('.js-select-theme').click(function(e) {
        e.preventDefault();

        var key = $(this).attr('key');

        // Deselect the current item.
        $('.theme-styles li').removeClass('active');

        // Select the new item.
        $(this).parents('li').addClass('active');
        $('#Form_StyleKey').val(key);
        $(this).parents('form').submit();

        // $(this).blur();
        return false;
    });
});
