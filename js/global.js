/**
 * Vanilla's legacy javascript core.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// Global vanilla library function.
(function(window, $) {

    // Prevent auto-execution of scripts when no explicit dataType was provided
    // See https://github.com/jquery/jquery/issues/2432#issuecomment-403761229
    jQuery.ajaxPrefilter(function(s) {
        if (s.crossDomain) {
            s.contents.script = false;
        }
    });

    var Vanilla = function() {
    };

    Vanilla.fn = Vanilla.prototype;

    if (!window.console)
        window.console = {
            log: function() {
            }
        };

    Vanilla.scrollTo = function(q) {
        var top = $(q).offset().top;
        window.scrollTo(0, top);
        return false;
    };

    // Add a stub for embedding.
    Vanilla.parent = function() {
    };
    Vanilla.parent.callRemote = function(func, args, success, failure) {
        console.log("callRemote stub: " + func, args);
    };

    window.gdn = window.gdn || {};
    window.Vanilla = Vanilla;

    gdn.getMeta = function(key, defaultValue) {
        if (gdn.meta[key] === undefined) {
            return defaultValue;
        } else {
            return gdn.meta[key];
        }
    };

    gdn.setMeta = function(key, value) {
        gdn.meta[key] = value;
    };

    var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    // See http://ecmanaut.blogspot.de/2006/07/encoding-decoding-utf8-in-javascript.html
    var uTF8Encode = function(string) {
        return decodeURI(encodeURIComponent(string));
    };

    // See http://ecmanaut.blogspot.de/2006/07/encoding-decoding-utf8-in-javascript.html
    var uTF8Decode = function(string) {
        return decodeURIComponent(escape(string));
    };

    $.extend({
        // private property
        keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
        base64Encode: function(input) {
            var output = "";
            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
            var i = 0;
            input = uTF8Encode(input);
            while (i < input.length) {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);
                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;
                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }
                output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
            }
            return output;
        },
        base64Decode: function(input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;
            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
            while (i < input.length) {
                enc1 = keyString.indexOf(input.charAt(i++));
                enc2 = keyString.indexOf(input.charAt(i++));
                enc3 = keyString.indexOf(input.charAt(i++));
                enc4 = keyString.indexOf(input.charAt(i++));
                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;
                output = output + String.fromCharCode(chr1);
                if (enc3 != 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 != 64) {
                    output = output + String.fromCharCode(chr3);
                }
            }
            output = uTF8Decode(output);
            return output;
        }
    });

    /**
     * Takes a jQuery function that updates the DOM and the HTML to add. Converts the html to a jQuery object
     * and then adds it to the DOM. Triggers 'contentLoad' to allow javascript manipulation of the new DOM elements.
     *
     * @param func The jQuery function name.
     * @param html The html to add.
     */
    var funcTrigger = function(func, html) {
        this.each(function() {
            var $elem = $($.parseHTML(html + '')); // Typecast html to a string and create a DOM node
            $(this)[func]($elem);
            $elem.trigger('contentLoad');
        });
        return this;
    };

    $.fn.extend({
        appendTrigger: function(html) {
            return funcTrigger.call(this, 'append', html);
        },

        beforeTrigger: function(html) {
            return funcTrigger.call(this, 'before', html);
        },

        afterTrigger: function(html) {
            return funcTrigger.call(this, 'after', html);
        },

        prependTrigger: function(html) {
            return funcTrigger.call(this, 'prepend', html);
        },

        htmlTrigger: function(html) {
            funcTrigger.call(this, 'html', html);
        },

        replaceWithTrigger: function(html) {
            return funcTrigger.call(this, 'replaceWith', html);
        }
    });

    $(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {
        var csrfToken = jqXHR.getResponseHeader("X-CSRF-Token");
        if (csrfToken) {
            gdn.setMeta("TransientKey", csrfToken);
            $("input[name=TransientKey]").val(csrfToken);
        }
    });

    // Hook into form submissions.  Replace element body with server response when we're in a .js-form.
    $(document).on("contentLoad", function (e) {
        $("form", e.target).submit(function (e) {
            var $form = $(this);

            // Traverse up the DOM, starting from the form that triggered the event, looking for the first .js-form.
            var $parent = $form.closest(".js-form");

            // Bail if we aren't in a .js-form.
            if ($parent.length === 0) {
                return;
            }

            // Hijack this submission.
            e.preventDefault();

            // An object containing extra data that should be submitted along with the form.
            var data = {
                DeliveryType: "VIEW"
            };

            var submitButton = $form.find("input[type=submit]:focus").get(0);
            if (submitButton) {
                data[submitButton.name] = submitButton.name;
            }

            // Send the request, expect HTML and hope for the best.
            $form.ajaxSubmit({
                data: data,
                dataType: "html",
                success: function (data, textStatus, jqXHR) {
                    $parent.html(data).trigger('contentLoad');
                }
            });
        });
    });

    $(document).on("contentLoad", function (e) {

        // Setup AJAX filtering for flat category module.
        // Find each flat category module container, if any.
        $(".BoxFlatCategory", e.target).each(function(index, value){
            // Setup the constants we'll need to perform the lookup for this module instance.
            var container = value;
            var categoryID = $("input[name=CategoryID]", container).val();
            var limit = parseInt($("input[name=Limit]", container).val());

            // If we don't even have a category, don't bother setting up filtering.
            if (typeof categoryID === "undefined") {
                return;
            }

            // limit was parsed as an int when originally defined.  If it isn't a valid value now, default to 10.
            if (isNaN(limit) || limit < 1) {
                limit = 10;
            }

            // Anytime someone types something into the search box in this instance's container...
            $(container).on("keyup", ".SearchForm .InputBox", function(filterEvent) {
                var url = gdn.url("module/flatcategorymodule/vanilla");

                // ...perform an AJAX request, replacing the current category data with the result's data.
                jQuery.get(
                    gdn.url("module/flatcategorymodule/vanilla"),
                    {
                        categoryID: categoryID,
                        filter: filterEvent.target.value,
                        limit: limit
                    },
                    function(data, textStatus, jqXHR) {
                        $(".FlatCategoryResult", container).replaceWith($(".FlatCategoryResult", data));
                    }
                )
            });
        });

        // A vanilla JS event wrapper for the contentLoad event so that the new framework can handle it.
        $(document).on("contentLoad", function(e) {
            // Don't fire on initial document ready.
            if (e.target === document) {
                return;
            }

            var event = document.createEvent('CustomEvent');
            event.initCustomEvent('X-DOMContentReady', true, false, {});
            e.target.dispatchEvent(event);
        });
    });
})(window, jQuery);

// Stuff to fire on document.ready().
jQuery(document).ready(function($) {

    /**
     * @deprecated since Vanilla 2.2
     */
    $.postParseJson = function(json) {
        return json;
    };

    gdn.focused = true;
    gdn.Libraries = {};

    $(window).blur(function() {
        gdn.focused = false;
    });
    $(window).focus(function() {
        gdn.focused = true;
    });

    // Grab a definition from object in the page
    gdn.definition = function(definition, defaultVal, set) {
        if (defaultVal === undefined)
            defaultVal = definition;

        if (!(definition in gdn.meta)) {
            return defaultVal;
        }

        if (set) {
            gdn.meta[definition] = defaultVal;
        }

        return gdn.meta[definition];
    };

    gdn.disable = function(e, progressClass) {
        var href = $(e).attr('href');
        if (href) {
            $.data(e, 'hrefBak', href);
        }
        $(e).addClass(progressClass ? progressClass : 'InProgress').removeAttr('href').attr('disabled', true);
    };

    gdn.enable = function(e) {
        $(e).attr('disabled', false).removeClass('InProgress');
        var href = $.data(e, 'hrefBak');
        if (href) {
            $(e).attr('href', href);
            $.removeData(e, 'hrefBak');
        }
    };

    gdn.elementSupports = function(element, attribute) {
        var test = document.createElement(element);
        if (attribute in test)
            return true;
        else
            return false;
    };

    gdn.querySep = function(url) {
        return url.indexOf('?') == -1 ? '?' : '&';
    };

    // password strength check
    gdn.password = function(password, username) {
        var translations = gdn.definition('PasswordTranslations', 'Too Short,Contains Username,Very Weak,Weak,Ok,Good,Strong').split(',');

        // calculate entropy
        var alphabet = 0;
        if (password.match(/[0-9]/))
            alphabet += 10;
        if (password.match(/[a-z]/))
            alphabet += 26;
        if (password.match(/[A-Z]/))
            alphabet += 26;
        if (password.match(/[^a-zA-Z0-9]/))
            alphabet += 31;
        var natLog = Math.log(Math.pow(alphabet, password.length));
        var entropy = natLog / Math.LN2;

        var response = {
            pass: false,
            symbols: alphabet,
            entropy: entropy,
            score: 0
        };

        // reject on length
        var length = password.length;
        response.length = length;
        var requiredLength = gdn.definition('MinPassLength', 6);
        var requiredScore = gdn.definition('MinPassScore', 2);
        response.required = requiredLength;
        if (length < requiredLength) {
            response.reason = translations[0];
            return response;
        }

        // password1 == username
        if (username) {
            if (password.toLowerCase().indexOf(username.toLowerCase()) >= 0) {
                response.reason = translations[1];
                return response;
            }
        }

        if (entropy < 30) {
            response.score = 1;
            response.reason = translations[2]; // very weak
        } else if (entropy < 40) {
            response.score = 2;
            response.reason = translations[3]; // weak
        } else if (entropy < 55) {
            response.score = 3;
            response.reason = translations[4]; // ok
        } else if (entropy < 70) {
            response.score = 4;
            response.reason = translations[5]; // good
        } else {
            response.score = 5;
            response.reason = translations[6]; // strong
        }

        return response;
    };

    // Go to notifications if clicking on a user's notification count
    $('li.UserNotifications a span').click(function() {
        document.location = gdn.url('/profile/notifications');
        return false;
    });

    /**
     * Add `rel='noopener'` to everything on the page.
     *
     * If you really need the linked page to have window.opener, set the `data-allow-opener='true'` on your link.
     */
    $("a[target='_blank']")
        .filter(":not([rel*='noopener']):not([data-allow-opener='true'])")
        .each(function() {
            var $this = $(this);
            var rel = $this.attr("rel");

            if (rel) {
                $this.attr("rel", rel + " noopener");
            } else {
                $this.attr("rel", "noopener");
            }
        });

    // This turns any anchor with the "Popup" class into an in-page pop-up (the
    // view of the requested in-garden link will be displayed in a popup on the
    // current screen).
    if ($.fn.popup) {

        // Previously, jquery.popup used live() to attach events, even to elements
        // that do not yet exist. live() has been deprecated. Vanilla upgraded
        // jQuery to version 1.10.2, which removed a lot of code.  Instead, event
        // delegation will need to be used, which means everywhere that Popup
        // is called, will need to have a very high up parent delegate to it.
        //$('a.Popup').popup();
        //$('a.PopConfirm').popup({'confirm' : true, 'followConfirm' : true});

        $('a.Popup:not(.dashboard a.Popup):not(.Section-Dashboard a.Popup)').popup();
        $('a.PopConfirm').popup({'confirm': true, 'followConfirm': true});
    }

    $(document).delegate(".PopupWindow:not(.Message .PopupWindow)", 'click', function() {
        var $this = $(this);

        if ($this.hasClass('NoMSIE') && /msie/.test(navigator.userAgent.toLowerCase())) {
            return;
        }

        var width = $this.attr('popupWidth');
        width = width ? width : 960;
        var height = $this.attr('popupHeight');
        height = height ? height : 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;

        var id = $this.attr('id');
        var href = $this.attr('href');
        if ($this.attr('popupHref'))
            href = $this.attr('popupHref');
        else
            href += gdn.querySep(href) + 'display=popup';

        var win = window.open(href, 'Window_' + id, "left=" + left + ",top=" + top + ",width=" + width + ",height=" + height + ",status=0,scrollbars=0");
        if (win)
            win.focus();
        return false;
    });

    // This turns any anchor with the "Popdown" class into an in-page pop-up, but
    // it does not hijack forms in the popup.
    if ($.fn.popup)
        $('a.Popdown').popup({hijackForms: false});

    // This turns SignInPopup anchors into in-page popups
    if ($.fn.popup)
        $('a.SignInPopup').popup({containerCssClass: 'SignInPopup'});

    if ($.fn.popup)
        $(document).delegate('.PopupClose', 'click', function(event) {
            var Popup = $(event.target).parents('.Popup');
            if (Popup.length) {
                var PopupID = Popup.prop('id');
                $.popup.close({popupId: PopupID});
            }
        });

    // Make sure that message dismissalls are ajax'd
    $(document).delegate('a.Dismiss', 'click', function() {
        var anchor = this;
        var container = $(anchor).parent();
        var transientKey = gdn.definition('TransientKey');
        var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;
        $.post($(anchor).attr('href'), data, function(response) {
            if (response == 'TRUE')
                $(container).fadeOut('fast', function() {
                    $(this).remove();
                });
        });
        return false;
    });

    // This turns any form into a "post-in-place" form so it is ajaxed to save
    // without a refresh. The form must be within an element with the "AjaxForm"
    // class.
    if ($.fn.handleAjaxForm)
        $('.AjaxForm').handleAjaxForm();

    // Handle ToggleMenu toggling and set up default state
    $('[class^="Toggle-"]').hide(); // hide all toggle containers
    $('.ToggleMenu a').click(function() {
        // Make all toggle buttons and toggle containers inactive
        $(this).parents('.ToggleMenu').find('li').removeClass('Active');
        $('[class^="Toggle-"]').hide();
        var item = $(this).parents('li'); // Identify the clicked container
        // The selector of the container that should be revealed.
        var containerSelector = '.Toggle-' + item.attr('class');
        containerSelector = containerSelector.replace(/Handle-/gi, '');
        // Reveal the container & make the button active
        item.addClass('Active'); // Make the clicked form button active
        $(containerSelector).show();
        return false;
    });
    $('.ToggleMenu .Active a').click(); // reveal the currently active item.

    // Show hoverhelp on hover
    $('.HoverHelp').hover(
        function() {
            $(this).find('.Help').show();
        },
        function() {
            $(this).find('.Help').hide();
        }
    );

    // If a page loads with a hidden redirect url, go there after a few moments.
    var redirectTo = gdn.getMeta('RedirectTo', '');
    var checkPopup = gdn.getMeta('CheckPopup', false);
    if (redirectTo !== '') {
        if (checkPopup && window.opener) {
            window.opener.location = redirectTo;
            window.close();
        } else {
            document.location = redirectTo;
        }
    }

    // Make tables sortable if the tableDnD plugin is present.
    if ($.tableDnD)
        $("table.Sortable").tableDnD({
            onDrop: function(table, row) {
                var tableId = $($.tableDnD.currentTable).attr('id');
                // Add in the transient key for postback authentication
                var transientKey = gdn.definition('TransientKey');
                var data = $.tableDnD.serialize() + '&TableID=' + tableId + '&TransientKey=' + transientKey;
                var webRoot = gdn.definition('WebRoot', '');
                $.post(
                    gdn.url('/utility/sort.json'),
                    data,
                    function(response) {
                        if (response.Result) {
                            $('#' + tableId + ' tbody tr td').effect("highlight", {}, 1000);
                        }
                    }
                );
            }
        });

    // Make sure that the commentbox & aboutbox do not allow more than 1000 characters
    $.fn.setMaxChars = function(iMaxChars) {
        $(this).bind('keyup', function() {
            var txt = $(this).val();
            if (txt.length > iMaxChars)
                $(this).val(txt.substr(0, iMaxChars));
        });
    };

    // Generate a random string of specified length
    gdn.generateString = function(length) {
        if (length === undefined)
            length = 5;

        var chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%*';
        var string = '';
        var pos = 0;
        for (var i = 0; i < length; i++) {
            pos = Math.floor(Math.random() * chars.length);
            string += chars.substring(pos, pos + 1);
        }
        return string;
    };

    // Combine two paths and make sure that there is only a single directory concatenator
    gdn.combinePaths = function(path1, path2) {
        if (path1.substr(-1, 1) == '/')
            path1 = path1.substr(0, path1.length - 1);

        if (path2.substring(0, 1) == '/')
            path2 = path2.substring(1);

        return path1 + '/' + path2;
    };

    gdn.processTargets = function(targets, $elem, $parent) {
        if (!targets || !targets.length)
            return;

        var tar = function(q) {
                switch (q) {
                    case '!element':
                        return $elem;
                    case '!parent':
                        return $parent;
                    default:
                        return q;
                }
            },
            item,
            $target;

        for (var i = 0; i < targets.length; i++) {
            item = targets[i];

            if (jQuery.isArray(item.Target)) {
                $target = $(tar(item.Target[0]), tar(item.Target[1]));
            } else {
                $target = $(tar(item.Target));
            }

            switch (item.Type) {
                case 'AddClass':
                    $target.addClass(item.Data);
                    break;
                case 'Ajax':
                    $.ajax({
                        type: "POST",
                        url: item.Data
                    });
                    break;
                case 'Append':
                    $target.appendTrigger(item.Data);
                    break;
                case 'Before':
                    $target.beforeTrigger(item.Data);
                    break;
                case 'After':
                    $target.afterTrigger(item.Data);
                    break;
                case 'Highlight':
                    $target.effect("highlight", {}, "slow");
                    break;
                case 'Prepend':
                    $target.prependTrigger(item.Data);
                    break;
                case 'Redirect':
                    window.location.replace(item.Data);
                    break;
                case 'Refresh':
                    window.location.reload();
                    break;
                case 'Remove':
                    $target.remove();
                    break;
                case 'RemoveClass':
                    $target.removeClass(item.Data);
                    break;
                case 'ReplaceWith':
                    $target.replaceWithTrigger(item.Data);
                    break;
                case 'SlideUp':
                    $target.slideUp('fast');
                    break;
                case 'SlideDown':
                    $target.slideDown('fast');
                    break;
                case 'Text':
                    $target.text(item.Data);
                    break;
                case 'Trigger':
                    $target.trigger(item.Data);
                    break;
                case 'Html':
                    $target.htmlTrigger(item.Data);
                    break;
                case 'Callback':
                    jQuery.proxy(window[item.Data], $target)();
                    break;
            }
        }
    };

    gdn.requires = function(Library) {
        if (!(Library instanceof Array))
            Library = [Library];

        var Response = true;

        $(Library).each(function(i, Lib) {
            // First check if we already have this library
            var LibAvailable = gdn.available(Lib);

            if (!LibAvailable) Response = false;

            // Skip any libs that are ready or processing
            if (gdn.Libraries[Lib] === false || gdn.Libraries[Lib] === true)
                return;

            // As yet unseen. Try to load
            gdn.Libraries[Lib] = false;
            var Src = '/js/' + Lib + '.js';
            var head = document.getElementsByTagName('head')[0];
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = Src;
            head.appendChild(script);
        });

        if (Response) gdn.loaded(null);
        return Response;
    };

    gdn.loaded = function(Library) {
        if (Library)
            gdn.Libraries[Library] = true;

        $(document).trigger('libraryloaded', [Library]);
    };

    gdn.available = function(Library) {
        if (!(Library instanceof Array))
            Library = [Library];

        for (var i = 0; i < Library.length; i++) {
            var Lib = Library[i];
            if (gdn.Libraries[Lib] !== true) return false;
        }
        return true;
    };

    gdn.url = function(path) {
        if (path.indexOf("//") >= 0)
            return path; // this is an absolute path.

        var urlFormat = gdn.definition("UrlFormat", "/{Path}");

        if (path.substr(0, 1) == "/")
            path = path.substr(1);

        if (urlFormat.indexOf("?") >= 0)
            path = path.replace("?", "&");

        return urlFormat.replace("{Path}", path);
    };

    // Fill in placeholders.
    if (!gdn.elementSupports('input', 'placeholder')) {
        $('input:text,textarea').not('.NoIE').each(function() {
            var $this = $(this);
            var placeholder = $this.attr('placeholder');

            if (!$this.val() && placeholder) {
                $this.val(placeholder);
                $this.blur(function() {
                    if ($this.val() === '')
                        $this.val(placeholder);
                });
                $this.focus(function() {
                    if ($this.val() == placeholder)
                        $this.val('');
                });
                $this.closest('form').bind('submit', function() {
                    if ($this.val() == placeholder)
                        $this.val('');
                });
            }
        });
    }

    $.fn.popin = function(options) {
        var settings = $.extend({}, options);

        this.each(function(i, elem) {
            var url = $(elem).attr('rel');
            var $elem = $(elem);
            $.ajax({
                url: gdn.url(url),
                data: {DeliveryType: 'VIEW'},
                success: function(data) {
                    $elem.html($.parseHTML(data + '')).trigger('contentLoad');
                },
                complete: function() {
                    $elem.removeClass('Progress TinyProgress InProgress');
                    if (settings.complete !== undefined) {
                        settings.complete($elem);
                    }
                }
            });
        });
    };
    $('.Popin, .js-popin').popin();

    // Make poplist items with a rel attribute clickable.
    $(document).on('click', '.PopList .Item[rel]', function() {
        window.location.href = $(this).attr('rel');
    });

    // Add a spinner onclick of buttons with this class
    $(document).delegate('input.SpinOnClick', 'click', function() {
        $(this).before('<span class="AfterButtonLoading">&#160;</span>').removeClass('SpinOnClick');
    });

    // Confirmation for item removals
    $('a.RemoveItem').click(function() {
        if (!confirm('Are you sure you would like to remove this item?')) {
            return false;
        }
    });

    if (window.location.hash === '') {
        // Jump to the hash if desired.
        if (gdn.definition('LocationHash', 0) !== 0) {
            $(window).load(function() {
                window.location.hash = gdn.definition('LocationHash');
            });
        }
        if (gdn.definition('ScrollTo', 0) !== 0) {
            var scrollTo = $(gdn.definition('ScrollTo'));
            if (scrollTo.length > 0) {
                $('html').animate({
                    scrollTop: scrollTo.offset().top - 10
                });
            }
        }
    }

    gdn.stats = function() {
        // Call directly back to the deployment and invoke the stats handler
        var StatsURL = gdn.getMeta('context')["dynamicPathFolder"] + gdn.url('/settings/analyticstick.json');
        var SendData = {
            'TransientKey': gdn.definition('TransientKey'),
            'Path': gdn.definition('Path'),
            'Args': gdn.definition('Args'),
            'ResolvedPath': gdn.definition('ResolvedPath'),
            'ResolvedArgs': gdn.definition('ResolvedArgs')
        };

        if (gdn.definition('TickExtra', null) !== null)
            SendData.TickExtra = gdn.definition('TickExtra');

        jQuery.ajax({
            dataType: 'json',
            type: 'post',
            url: StatsURL,
            data: SendData,
            success: function(json) {
                gdn.inform(json);
            },
            complete: function(jqXHR, textStatus) {
                jQuery(document).triggerHandler('analyticsTick', [SendData, jqXHR, textStatus]);
            }
        });
    };

    // Ping back to the deployment server to track views, and trigger
    // conditional stats tasks
    var AnalyticsTask = gdn.definition('AnalyticsTask', false);
    if (AnalyticsTask == 'tick')
        gdn.stats();

    // If a dismissable InformMessage close button is clicked, hide it.
    $(document).delegate('div.InformWrapper.Dismissable a.Close, div.InformWrapper .js-inform-close', 'click', function() {
        $(this).parents('div.InformWrapper').fadeOut('fast', function() {
            $(this).remove();
        });
    });

    gdn.setAutoDismiss = function() {
        var timerId = $('div.InformMessages').attr('autodismisstimerid');
        if (!timerId) {
            timerId = setTimeout(function() {
                $('div.InformWrapper.AutoDismiss').fadeOut('fast', function() {
                    $(this).remove();
                });
                $('div.InformMessages').removeAttr('autodismisstimerid');
            }, 7000);
            $('div.InformMessages').attr('autodismisstimerid', timerId);
        }
    };

    // Handle autodismissals
	$(document).on('informMessage', function() {
		gdn.setAutoDismiss();
	});

    // Prevent autodismiss if hovering any inform messages
    $(document).delegate('div.InformWrapper', 'mouseover mouseout', function(e) {
        if (e.type == 'mouseover') {
            var timerId = $('div.InformMessages').attr('autodismisstimerid');
            if (timerId) {
                clearTimeout(timerId);
                $('div.InformMessages').removeAttr('autodismisstimerid');
            }
        } else {
            gdn.setAutoDismiss();
        }
    });

    // Take any "inform" messages out of an ajax response and display them on the screen.
    gdn.inform = function(response) {
        if (!response)
            return false;

        if (!response.InformMessages || response.InformMessages.length === 0)
            return false;

        // If there is no message container in the page, add one
        var informMessages = $('div.InformMessages');
        if (informMessages.length === 0) {
            $('<div class="InformMessages"></div>').appendTo('body');
            informMessages = $('div.InformMessages');
        }
        var wrappers = $('div.InformMessages div.InformWrapper'),
            css,
            elementId,
            sprite,
            dismissCallback,
            dismissCallbackUrl;

        // Loop through the inform messages and add them to the container
        for (var i = 0; i < response.InformMessages.length; i++) {
            css = 'InformWrapper';
            if (response.InformMessages[i].CssClass)
                css += ' ' + response.InformMessages[i].CssClass;

            elementId = '';
            if (response.InformMessages[i].id)
                elementId = response.InformMessages[i].id;

            sprite = '';
            if (response.InformMessages[i].Sprite) {
                css += ' HasSprite';
                sprite = response.InformMessages[i].Sprite;
            }

            dismissCallback = response.InformMessages[i].DismissCallback;
            dismissCallbackUrl = response.InformMessages[i].DismissCallbackUrl;
            if (dismissCallbackUrl)
                dismissCallbackUrl = gdn.url(dismissCallbackUrl);

            try {
                var message = response.InformMessages[i].Message;
                var emptyMessage = message === '';

                message = '<span class="InformMessageBody">' + message + '</span>';

                // Is there a sprite?
                if (sprite !== '')
                    message = '<span class="InformSprite ' + sprite + '"></span>';

                // If the message is dismissable, add a close button
                if (css.indexOf('Dismissable') > 0)
                    message = '<a href="#" onclick="return false;" tabindex="0" class="Close"><span>&times;</span></a>' + message;

                message = '<div class="InformMessage">' + message + '</div>';
                // Insert any transient keys into the message (prevents csrf attacks in follow-on action urls).
                message = message.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
                if (gdn.getMeta('SelfUrl')) {
                    // If the url is explicitly defined (as in embed), use it.
                    message = message.replace(/{SelfUrl}/g, gdn.getMeta('SelfUrl'));
                } else {
                    // Insert the current url as a target for inform anchors
                    message = message.replace(/{SelfUrl}/g, document.URL);
                }
                var skip = false;
                for (var j = 0; j < wrappers.length; j++) {
                    if ($(wrappers[j]).text() == $(message).text()) {
                        skip = true;
                    }
                }
                if (!skip) {
                    if (elementId !== '') {
                        $('#' + elementId).remove();
                        elementId = ' id="' + elementId + '"';
                    }
                    if (!emptyMessage) {
                        informMessages.prependTrigger('<div class="' + css + '"' + elementId + '>' + message + '</div>');
                        // Is there a callback or callback url to request on dismiss of the inform message?
                        if (dismissCallback) {
                            $('div.InformWrapper:first').find('a.Close').click(eval(dismissCallback));
                        } else if (dismissCallbackUrl) {
                            dismissCallbackUrl = dismissCallbackUrl.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
                            var closeAnchor = $('div.InformWrapper:first').find('a.Close');
                            closeAnchor.attr('callbackurl', dismissCallbackUrl);
                            closeAnchor.click(function() {
                                $.ajax({
                                    type: "POST",
                                    url: $(this).attr('callbackurl'),
                                    data: 'TransientKey=' + gdn.definition('TransientKey'),
                                    dataType: 'json',
                                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                                        gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
                                    },
                                    success: function(json) {
                                        gdn.inform(json);
                                    }
                                });
                            });
                        }
                    }
                }
            } catch (e) {
            }
        }
        informMessages.show();
        $(document).trigger('informMessage');
        return true;
    };

    // Send an informMessage to the screen (same arguments as controller.InformMessage).
    gdn.informMessage = function(message, options) {
        if (!options)
            options = [];

        if (typeof(options) == 'string') {
            var css = options;
            options = [];
            options.CssClass = css;
        }
        options.Message = message;
        if (!options.CssClass)
            options.CssClass = 'Dismissable AutoDismiss';

        gdn.inform({'InformMessages': new Array(options)});
    };

    // Inform an error returned from an ajax call.
    gdn.informError = function(xhr, silentAbort) {
        if (xhr === undefined || xhr === null)
            return;

        if (typeof(xhr) == 'string')
            xhr = {responseText: xhr, code: 500};

        var message = xhr.responseText;
        var code = xhr.status;

        if (!message) {
            switch (xhr.statusText) {
                case 'error':
                    if (silentAbort)
                        return;
                    message = 'There was an error performing your request. Please try again.';
                    break;
                case 'timeout':
                    message = 'Your request timed out. Please try again.';
                    break;
                case 'abort':
                    return;
            }
        }

        try {
            var data = $.parseJSON(message);
            if (typeof(data.Exception) == 'string')
                message = data.Exception;
        } catch (e) {
        }

        if (message === '')
            message = 'There was an error performing your request. Please try again.';

        gdn.informMessage('<span class="InformSprite Lightbulb Error' + code + '"></span>' + message, 'HasSprite Dismissable');
    };

    // Pick up the inform message stack and display it on page load
    var informMessageStack = gdn.definition('InformMessageStack', false);
    if (informMessageStack) {
        var informMessages;
        try {
            informMessages = $.parseJSON(informMessageStack);
            informMessageStack = {'InformMessages': informMessages};
            gdn.inform(informMessageStack);
        } catch (e) {
            console.log('informMessageStack contained invalid JSON');
        }
    }

    // Ping for new notifications on pageload, and subsequently every 1 minute.
    var notificationsPinging = 0, pingCount = 0;
    var pingForNotifications = function() {
        if (notificationsPinging > 0 || !gdn.focused)
            return;
        notificationsPinging++;

        $.ajax({
            type: "POST",
            url: gdn.getMeta('context')["dynamicPathFolder"] + gdn.url('/notifications/inform'),
            data: {
                'TransientKey': gdn.definition('TransientKey'),
                'Path': gdn.definition('Path'),
                'DeliveryMethod': 'JSON',
                'Count': pingCount++
            },
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log(XMLHttpRequest.responseText);
            },
            success: function(json) {
                gdn.inform(json);
            },
            complete: function() {
                notificationsPinging--;
            }
        });
    };
    gdn.pingForNotifications = pingForNotifications;

    if (gdn.definition('SignedIn', '0') != '0' && gdn.definition('DoInform', '1') != '0') {
        setTimeout(pingForNotifications, 3000);
        setInterval(pingForNotifications, 60000);
    }

    // Clear notifications alerts when they are accessed anywhere.
    $(document).on('click', '.js-clear-notifications', function() {
        $('.NotificationsAlert').remove();
    });

    $(document).on('change', '.js-nav-dropdown', function() {
        window.location = $(this).val();
    });

    // Stash something in the user's session (or unstash the value if it was not provided)
    var stash = function(name, value, callback) {
        $.ajax({
            type: "POST",
            url: gdn.url('session/stash'),
            data: {'TransientKey': gdn.definition('TransientKey'), 'Name': name, 'Value': value},
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
            },
            success: function(json) {
                gdn.inform(json);

                if (typeof(callback) === 'function') {
                    callback();
                } else {
                    return json.Unstash;
                }
            }
        });

        return '';
    };

    // When a stash anchor is clicked, look for inputs with values to stash
    $('a.Stash').click(function(e) {
        var comment = $('#Form_Comment textarea').val(),
            placeholder = $('#Form_Comment textarea').attr('placeholder'),
            stash_name;

        // Stash a comment:
        if (comment !== '' && comment !== placeholder) {
            var vanilla_identifier = gdn.definition('vanilla_identifier', false);

            if (vanilla_identifier) {
                // Embedded comment:
                stash_name = 'CommentForForeignID_' + vanilla_identifier;
            } else {
                // Non-embedded comment:
                stash_name = 'CommentForDiscussionID_' + gdn.definition('DiscussionID');
            }
            var href = $(this).attr('href');
            e.preventDefault();

            stash(stash_name, comment, function() {
                window.top.location = href;
            });
        }
    });

    String.prototype.addCommas = function() {
        var nStr = this,
            x = nStr.split('.'),
            x1 = x[0],
            x2 = x.length > 1 ? '.' + x[1] : '',
            rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    };

    Array.prototype.sum = function() {
        for (var i = 0, sum = 0; i < this.length; sum += this[i++]);
        return sum;
    };

    Array.prototype.max = function() {
        return Math.max.apply({}, this);
    };

    Array.prototype.min = function() {
        return Math.min.apply({}, this);
    };

    if (/msie/.test(navigator.userAgent.toLowerCase())) {
        $('body').addClass('MSIE');
    }

    var d = new Date();
    var hourOffset = -Math.round(d.getTimezoneOffset() / 60);
    var tz = false;

    /**
     * ECMAScript Internationalization API is supported by all modern browsers, with the exception of Safari.  We use
     * it here, with lots of careful checking, to attempt to fetch the user's current IANA time zone string.
     */
    if (typeof Intl === 'object' && typeof Intl.DateTimeFormat === 'function') {
        var dateTimeFormat = Intl.DateTimeFormat();
        if (typeof dateTimeFormat.resolvedOptions === 'function') {
            var resolvedOptions = dateTimeFormat.resolvedOptions();
            if (typeof resolvedOptions === 'object' && typeof resolvedOptions.timeZone === 'string') {
                tz = resolvedOptions.timeZone;
            }
        }
    }

    // Ajax/Save the ClientHour if it is different from the value in the db.
    var setHourOffset = parseInt(gdn.definition('SetHourOffset', hourOffset));
    var setTimeZone = gdn.definition('SetTimeZone', tz);
    if (hourOffset !== setHourOffset || (tz && tz !== setTimeZone)) {
        $.post(
            gdn.url('/utility/sethouroffset.json'),
            {HourOffset: hourOffset, TimeZone: tz, TransientKey: gdn.definition('TransientKey')}
        );
    }

    // Add "checked" class to item rows if checkboxes are checked within.
    var checkItems = function() {
        var container = $(this).parents('.Item');
        if ($(this).prop('checked'))
            $(container).addClass('Checked');
        else
            $(container).removeClass('Checked');
    };
    $('.Item :checkbox').each(checkItems);
    $('.Item :checkbox').change(checkItems);

    // If we are not inside an iframe, focus the email input on the signin page.
    if ($('#Form_User_SignIn').length && window.top.location === window.location) {
        $('#Form_Email').focus();
    }

    // Convert date fields to datepickers
    if ($.fn.datepicker) {
        $('input.DatePicker').datepicker({
            showOn: "focus",
            dateFormat: 'mm/dd/yy'
        });
    }

    /**
     * Youtube preview revealing
     *
     */

    // Reveal youtube player when preview clicked.
    function Youtube($container) {
        var $preview = $container.find('.VideoPreview');
        var $player = $container.find('.VideoPlayer');

        $container.addClass('Open').closest('.ImgExt').addClass('Open');

        var width = $preview.width(), height = $preview.height(), videoid = '';

        try {
            videoid = $container.attr('data-youtube').replace('youtube-', '');
        } catch (e) {
            console.log("YouTube parser found invalid id attribute: " + videoid);
        }


        // Verify we have a valid videoid
        var pattern = /^[\w-]+(\?autoplay\=1)(\&start=[\w-]+)?(\&rel=.)?$/;
        if (!pattern.test(videoid)) {
            return false;
        }

        var html = '<iframe width="' + width + '" height="' + height + '" src="https://www.youtube.com/embed/' + videoid + '" frameborder="0" allowfullscreen></iframe>';
        $player.html(html);

        $preview.hide();
        $player.show();

        return false;
    }

    $(document).delegate('.Video.YouTube .VideoPreview', 'click', function(e) {
        var $target = $(e.target);
        var $container = $target.closest('.Video.YouTube');
        return Youtube($container);
    });

    /**
     * Pintrest pin embedding
     *
     */

    if ($('a.pintrest-pin').length) {
        (function(d) {
            var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
            p.type = 'text/javascript';
            p.async = true;
            p.src = '//assets.pinterest.com/js/pinit.js';
            f.parentNode.insertBefore(p, f);
        }(document));
    }

    /**
     * Textarea autosize.
     *
     * Create wrapper for autosize library, so that the custom
     * arguments passed do not need to be repeated for every call, if for some
     * reason it needs to be binded elsewhere and the UX should be identical,
     * otherwise just use autosize directly, passing arguments or none.
     *
     * Note: there should be no other calls to autosize, except for in this file.
     * All previous calls to the old jquery.autogrow were called in their
     * own files, which made managing this functionality less than optimal. Now
     * all textareas will have autosize binded to them by default.
     *
     * @depends js/library/jquery.autosize.min.js
     */
    gdn.autosize = function(textarea) {
        // Check if library available.
        if ($.fn.autosize) {
            // Check if not already active on node.
            if (!$(textarea).hasClass('textarea-autosize')) {
                $(textarea).autosize({
                    append: '\n',
                    resizeDelay: 20, // Keep higher than CSS transition, else creep.
                    callback: function(el) {
                        // This class adds the transition, and removes manual resize.
                        $(el).addClass('textarea-autosize');
                    }
                });
                // Otherwise just trigger a resize refresh.
            } else {
                $(textarea).trigger('autosize.resize');
            }
        }
    };

    /**
     * Bind autosize to relevant nodes.
     *
     * Attach autosize to all textareas. Previously this existed across multiple
     * files, probably as it was slowly incorporated into different areas, but
     * at this point it's safe to call it once here. The wrapper above makes
     * sure that it will not throw any errors if the library is unavailable.
     *
     * Note: if there is a textarea not autosizing, it would be good to find out
     * if there is another event for that exception, and if all fails, there
     * is the livequery fallback, which is not recommended.
     */
    gdn.initAutosizeEvents = (function() {
        $('textarea').each(function(i, el) {
            // Attach to all immediately available textareas.
            gdn.autosize(el);

            // Also, make sure that focus on the textarea will trigger a resize,
            // just to cover all possibilities.
            $(el).on('focus', function(e) {
                gdn.autosize(this);
            });
        });

        // For any dynamically loaded textareas that are inserted and have an
        // event triggered to grab their node, or just events that should call
        // a resize on the textarea. Attempted to bind to `appendHtml` event,
        // but it required a (0ms) timeout, so it's being kept in Quotes plugin,
        // where it's actually triggered.
        var autosizeTriggers = [
            'clearCommentForm',
            'popupReveal',
            'contentLoad'
        ];

        $(document).on(autosizeTriggers.join(' '), function(e, data) {
            data = (typeof data == 'object') ? data : '';
            $(data || e.target || this).parent().find('textarea').each(function(i, el) {
                gdn.autosize(el);
            });
        });
    }());

    // http://stackoverflow.com/questions/118241/calculate-text-width-with-javascript
    String.prototype.width = function(font) {
        var f = font || "15px 'lucida grande','Lucida Sans Unicode',tahoma,sans-serif'",
            o = $('<div>' + this + '</div>')
                .css({
                    'position': 'absolute',
                    'float': 'left',
                    'white-space': 'nowrap',
                    'visibility': 'hidden',
                    'font': f
                })
                .appendTo($('body')),
            w = o.width();
        o.remove();
        return w;
    };

    /**
     * Running magnific-popup. Image tag or text must be wrapped with an anchor
     * tag. This will render the content of the anchor tag's href. If using an
     * image tag, the anchor tag's href can point to either the same location
     * as the image tag, or a higher quality version of the image. If zoom is
     * not wanted, remove the zoom and mainClass properties, and it will just
     * load the content of the anchor tag with no special effects.
     *
     * @documentation http://dimsemenov.com/plugins/magnific-popup/documentation.html
     *
     */
    gdn.magnificPopup = (function() {
        if ($.fn.magnificPopup) {
            $('.mfp-image').each(function(i, el) {
                $(el).magnificPopup({
                    type: 'image',
                    mainClass: 'mfp-with-zoom',
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease',
                        opener: function(openerElement) {
                            return openerElement.is('img')
                                ? openerElement
                                : openerElement.find('img');
                        }
                    }
                });
            });
        }
    }());

    /**
     * A kludge to dodge Safari's back-forward cache (bfcache).  Without this, Safari maintains
     * the a page's DOM during back/forward navigation and hinders our ability to invalidate
     * the cached state of content.
     */
    if (/Apple Computer/.test(navigator.vendor) && /Safari/.test(navigator.userAgent)) {
        jQuery(window).on("pageshow", function(event) {
            if (event.originalEvent.persisted) {
                window.location.reload();
            }
        });
    }

    $(document).trigger('contentLoad');
});

// Shrink large images to fit into message space, and pop into new window when clicked.
// This needs to happen in onload because otherwise the image sizes are not yet known.
jQuery(window).load(function() {
    /*
     Adds .naturalWidth() and .naturalHeight() methods to jQuery for retreaving a
     normalized naturalWidth and naturalHeight.
     // Example usage:
     var
     nWidth = $('img#example').naturalWidth(),
     nHeight = $('img#example').naturalHeight();
     */

    (function($) {
        var props = ['Width', 'Height'],
            prop;

        while (prop = props.pop()) {
            (function(natural, prop) {
                $.fn[natural] = (natural in new Image()) ?
                    function() {
                        return this[0][natural];
                    } :
                    function() {
                        var node = this[0],
                            img,
                            value;

                        if (node.tagName.toLowerCase() === 'img') {
                            img = new Image();
                            img.src = node.src;
                            value = img[prop];
                        }
                        return value;
                    };
            }('natural' + prop, prop.toLowerCase()));
        }
    }(jQuery));

    jQuery('div.Message img')
        .not(jQuery('div.Message a > img'))
        .not(jQuery('.js-embed img'))
        .not(jQuery('.embedImage-img'))
        .each(function (i, img){
            img = jQuery(img);
            var container = img.closest('div.Message');
            if (img.naturalWidth() > container.width() && container.width() > 0) {
                img.wrap('<a href="' + jQuery(img).attr('src') + '" target="_blank" rel="nofollow noopener"></a>');
            }
        });

    // Let the world know we're done here
    jQuery(window).trigger('ImagesResized');
});

if (typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g, '');
    };
}

(function ($) {
    $.fn.extend({
    // jQuery UI .effect() replacement using CSS classes.
    effect: function(name) {
        var that = this;
        name = name + '-effect';

        return this
            .addClass(name)
            .one('animationend webkitAnimationEnd', function () {
                that.removeClass(name);
            });
    },
});

})(jQuery);
