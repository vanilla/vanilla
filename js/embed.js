if (window.vanilla == undefined)
    window.vanilla = {};

if (window.vanilla.embeds == undefined)
    window.vanilla.embeds = {};

window.vanilla.embed = function(host) {
    var scripts = document.getElementsByTagName('script'),
        id = Math.floor((Math.random()) * 100000).toString(),
        embedUrl = window.location.href.split('#')[0],
        jsPath = '/js/embed.js',
        currentPath = window.location.hash.substr(1),
        disablePath = (window != top);

    var optStr = function(name, defaultValue, definedValue) {
        if (window['vanilla_' + name]) {
            if (definedValue == undefined)
                return window['vanilla_' + name];
            else
                return definedValue.replace('%s', window['vanilla_' + name]);
        }
        return defaultValue;
    }

    if (!currentPath || disablePath)
        currentPath = "/";

    if (currentPath.substr(0, 1) != '/')
        currentPath = '/' + currentPath;

    if (window.gadgets)
        embedUrl = '';

    if (typeof(host) == 'undefined') {
        host = '';
        host_base_url = '';
        for (i = 0; i < scripts.length; i++) {
            if (scripts[i].src.indexOf(jsPath) > 0) {
                host = scripts[i].src;
                host = host.replace('http://', '').replace('https://', '');
                host = host.substr(0, host.indexOf(jsPath));
                host += '/index.php?p=';

                host_base_url = scripts[i].src;
                host_base_url = host_base_url.substr(0, host_base_url.indexOf(jsPath));
                if (host_base_url.substring(host_base_url.length - 1) != '/')
                    host_base_url += '/';

            }
        }
    }

    window.vanilla.embeds[id] = this;
    if (window.postMessage) {
        onMessage = function(e) {
            // Check that we're getting a vanilla message
            if ((typeof e.data) === 'string') {
                var message = e.data.split(':');
                var frame = document.getElementById('vanilla' + id);

                // Unload event's source is undefined
                var isUnload = message[0] == 'unload';
                if (!frame || (!isUnload && frame.contentWindow != e.source)) {
                    return;
                }
                processMessage(message);
            }
        };
        if (window.addEventListener) {
            window.addEventListener("message", onMessage, false);
        } else {
            window.attachEvent("onmessage", onMessage);
        }
    } else {
        var messageId = null;
        setInterval(function() {
            try {
                var vid = 'vanilla' + id;
                var hash = window.frames[vid].frames['messageFrame'].location.hash;
                hash = hash.substr(6);
            } catch (e) {
                return;
            }

            var message = hash.split(':');
            var newMessageId = message[0];
            if (newMessageId == messageId) {
                return;
            }

            messageId = newMessageId;
            message.splice(0, 1);
            processMessage(message);
        }, 200);
    }

    checkHash = function() {
        var path = window.location.hash.substr(1) || "/";
        if (path != currentPath) {
            currentPath = path;
            window.frames['vanilla' + id].location.replace(vanillaUrl(path));
        }
    };

    if (!window.gadgets) {
        if (!disablePath) {
            if ("onhashchange" in window) {
                if (window.addEventListener)
                    window.addEventListener("hashchange", checkHash, false);
                else
                    window.attachEvent("onhashchange", checkHash);
            } else {
                setInterval(checkHash, 300);
            }
        }
    }

    // Strip param out of str if it exists
    stripParam = function(str, param) {
        var pIndex = str.indexOf(param);
        if (pIndex > -1) {
            var pStr = str.substr(pIndex);
            var tIndex = pStr.indexOf('&');
            var trail = tIndex > -1 ? pStr.substr(tIndex + 1) : '';
            var pre = currentPath.substr(pIndex - 1, 1);
            if (pre == '&' || pre == '?')
                pIndex--;

            return str.substr(0, pIndex) + (trail.length > 0 ? pre : '') + trail;
        }
        return str;
    }

    processMessage = function(message) {
        var iframe = document.getElementById('vanilla' + id);

        if (message[0] == 'height') {
            setHeight(message[1]);

            if (message[1] > 0) {
                iframe.style.visibility = "visible";
            }

        } else if (message[0] == 'location') {

            if (message[1] != '') {
                iframe.style.visibility = "visible";
            }

            if (disablePath) {
                //currentPath = cmd[1];
            } else {
                currentPath = window.location.hash.substr(1);
                if (currentPath != message[1]) {
                    currentPath = message[1];
                    // Strip off the values that this script added
                    currentPath = currentPath.replace('/index.php?p=', ''); // 1
                    currentPath = stripParam(currentPath, 'remote='); // 2
                    currentPath = stripParam(currentPath, 'locale='); // 3
                    window.location.hash = currentPath;
                }
            }
        } else if (message[0] == 'unload') {
            // Scroll to the top of the IFRAME if the top position is not in the view.
            var currentScrollAmount = (window.pageYOffset || document.documentElement.scrollTop);
            var offsetTop = offsetFromTop('vanilla' + id);
            if (offsetTop - currentScrollAmount < 0) {
                window.scrollTo(0, offsetTop);
            }

            iframe.style.visibility = "hidden";

        } else if (message[0] == 'scrolltop') {
            window.scrollTo(0, document.getElementById('vanilla' + id).offsetTop);
        } else if (message[0] == 'scrollto') {
            window.scrollTo(0, document.getElementById('vanilla' + id).offsetTop - 40 + (message[1] * 1));
        } else if (message[0] == 'unembed') {
            document.location = 'http://' + host + window.location.hash.substr(1);
        }
    }

    offsetFromTop = function(id) {
        var node = document.getElementById(id),
            top = 0,
            topScroll = 0;
        if (node.offsetParent) {
            do {
                top += node.offsetTop;
                topScroll += node.offsetParent ? node.offsetParent.scrollTop : 0;
            } while (node = node.offsetParent);
            return top - topScroll;
        }
        return -1;
    }

    setHeight = function(height) {
        if (optStr('height'))
            return;

        document.getElementById('vanilla' + id).style['height'] = height + "px";
        if (window.gadgets && gadgets.window && gadgets.window.adjustHeight) {
            try {
                gadgets.window.adjustHeight();
            } catch (ex) {
                // Do nothing...
            }
        }
    }

    vanillaUrl = function(path) {
        // What type of embed are we performing?
        var embed_type = typeof(vanilla_embed_type) == 'undefined' ? 'standard' : vanilla_embed_type;
        // Are we loading a particular discussion based on discussion_id?
        var discussion_id = typeof(vanilla_discussion_id) == 'undefined' ? 0 : vanilla_discussion_id;
        // Are we loading a particular discussion based on foreign_id?
        var foreign_id = typeof(vanilla_identifier) == 'undefined' ? '' : vanilla_identifier;
        // Is there a foreign type defined? Possibly used to render the discussion
        // body a certain way in the forum? Also used to filter down to foreign
        // types so that matching foreign_id's across type don't clash.
        var foreign_type = typeof(vanilla_type) == 'undefined' ? 'page' : vanilla_type;
        // If embedding comments, should the newly created discussion be placed in a specific category?
        var category_id = typeof(vanilla_category_id) == 'undefined' ? '' : vanilla_category_id;
        // If embedding comments, this value will be used to reference the foreign content. Defaults to the url of the page this file is included in.
        var foreign_url = typeof(vanilla_url) == 'undefined' ? document.URL.split('#')[0] : vanilla_url;
        // Are we forcing a locale via Multilingual plugin?
        var embed_locale = typeof(vanilla_embed_locale) == 'undefined' ? '' : vanilla_embed_locale;
        if (typeof(vanilla_lazy_load) == 'undefined')
            vanilla_lazy_load = true;

        // If path was defined, and we're sitting at app root, use the defined path instead.
        if (typeof(vanilla_path) != 'undefined' && path == '/')
            path = vanilla_path;

        // Force type based on incoming variables
        if (discussion_id != '' || foreign_id != '')
            embed_type = 'comments';

        var result = '';

        if (embed_type == 'comments') {
            result = '//' + host + '/discussion/embed/'
            + '&vanilla_identifier=' + encodeURIComponent(foreign_id)
            + '&vanilla_url=' + encodeURIComponent(foreign_url);

            if (typeof(vanilla_type) != 'undefined')
                result += '&vanilla_type=' + encodeURIComponent(vanilla_type)

            if (typeof(vanilla_discussion_id) != 'undefined')
                result += '&vanilla_discussion_id=' + encodeURIComponent(vanilla_discussion_id);

            if (typeof(vanilla_category_id) != 'undefined')
                result += '&vanilla_category_id=' + encodeURIComponent(vanilla_category_id);

            if (typeof(vanilla_title) != 'undefined')
                result += '&title=' + encodeURIComponent(vanilla_title);
        } else {
            result = '//'
            + host
            + path
            + '&remote='
            + encodeURIComponent(embedUrl)
            + '&locale='
            + encodeURIComponent(embed_locale);
        }

        if (window.vanilla_sso) {
            result += '&sso=' + encodeURIComponent(vanilla_sso);
        }

        return result.replace(/\?/g, '&').replace('&', '?'); // Replace the first occurrence of amp with question.
    }
    var vanillaIframe = document.createElement('iframe');
    vanillaIframe.id = "vanilla" + id;
    vanillaIframe.name = "vanilla" + id;
    vanillaIframe.src = vanillaUrl(currentPath);
    vanillaIframe.scrolling = "no";
    vanillaIframe.frameBorder = "0";
    vanillaIframe.allowTransparency = true;
    vanillaIframe.border = "0";
    vanillaIframe.width = "100%";
    vanillaIframe.style.width = "100%";
    vanillaIframe.style.border = "0";
    vanillaIframe.style.display = "block"; // must be block

    if (window.postMessage) {
        vanillaIframe.height = "0";
        vanillaIframe.style.height = "0";
    } else {
        vanillaIframe.height = "300";
        vanillaIframe.style.height = "300px";
    }


    var img = document.createElement('div');
    img.className = 'vn-loading';
    img.style.textAlign = 'center';
    img.innerHTML = window.vanilla_loadinghtml ? vanilla_loadinghtml : '<img src="https://images.v-cdn.net/progress.gif" />';

    var container = document.getElementById('vanilla-comments');
    // Couldn't find the container, so dump it out and try again.
    if (!container)
        document.write('<div id="vanilla-comments"></div>');
    container = document.getElementById('vanilla-comments');

    if (container) {
        var loaded = function() {
            if (img) {
                container.removeChild(img);
                img = null;
            }
            vanillaIframe.style.visibility = "visible";
        };

        if (vanillaIframe.addEventListener) {
            vanillaIframe.addEventListener('load', loaded, true);
        } else if (vanillaIframe.attachEvent) {
            vanillaIframe.attachEvent('onload', loaded);
        } else
            setTimeout(2000, loaded);

        container.appendChild(img);

        // If jQuery is present in the page, include our defer-until-visible script
        if (vanilla_lazy_load && typeof jQuery != 'undefined') {
            jQuery.ajax({
                url: host_base_url + 'js/library/jquery.appear.js',
                dataType: 'script',
                cache: true,
                success: function() {
//               setTimeout(function() {

                    if (jQuery.fn.appear)
                        jQuery('#vanilla-comments').appear(function() {
                            container.appendChild(vanillaIframe);
                        });
                    else
                        container.appendChild(vanillaIframe); // fallback
//               }, 10000);
                }
            });
        } else {
            container.appendChild(vanillaIframe); // fallback: just load it
        }
    }

    // Include our embed css into the page
    var vanilla_embed_css = document.createElement('link');
    vanilla_embed_css.rel = 'stylesheet';
    vanilla_embed_css.type = 'text/css';
    vanilla_embed_css.href = host_base_url + (host_base_url.substring(host_base_url.length - 1) == '/' ? '' : '/') + 'applications/dashboard/design/embed.css';
    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_embed_css);

    return this;
};
try {
    if (window.location.hash.substr(0, 6) != "#poll:")
        window.vanilla.embed();
} catch (e) {
    var error = document.createElement('div');
    error.style.padding = "10px";
    error.style.fontSize = "12px";
    error.style.fontFamily = "lucida grande";
    error.style.background = "#ffffff";
    error.style.color = "#000000";
    error.appendChild(document.createTextNode("Failed to embed Vanilla: " + e));
    (document.getElementById('vanilla-comments')).appendChild(error);
}
