jQuery(document).ready(function($) {
    if (typeof(gdn) == "undefined") {
        gdn = {};
        gdn.definition = function() {
            return '';
        }
    }

    var currentHeight = null,
        minHeight = 100,
        remotePostMessage = function(message, target) {
        },
        remoteUrl = gdn.definition('RemoteUrl', ''),
        inIframe = top !== self,
        inPopup = window.opener != null,
        inConnect = window.location.pathname.indexOf("/entry/connect") >= 0,
        inDashboard = gdn.definition('InDashboard') == '1',
        forceEmbedDashboard = gdn.definition('ForceEmbedDashboard') == '1',
        forceEmbedForum = gdn.definition('ForceEmbedForum') == '1',
        isEmbeddedComments = gdn.definition('Embedded', '') != '',
        webroot = gdn.definition('WebRoot'),
        path = gdn.definition('Path', '~');
    if (path.length > 0 && path[0] != '/')
        path = '/' + path;
    /*
     Embedded pages can have very low height settings. As a result, when an
     absolutely positioned popup appears on the page, iframed content doesn't
     know to increase the page height. So, we need to detect when popups appear
     and increase the page height manually so the container knows to do the same.
     */
    popupHeight = function() {
        var height = Math.round(($.popup.getPagePosition().top * 1) + ($('.Popup').height() * 1));
        if (height > minHeight && height > document.body.offsetHeight) {
            setHeight(height); // Set it immediately to prevent content being cut off.
            $('body').css('minHeight', height + 'px');
        }
    }

    if (inIframe) {
        $('body').bind('popupLoading', popupHeight); // set it when popup loading window appears
        $('body').bind('popupReveal', popupHeight); // reset it when the final popup is revealed

        if ("postMessage" in parent) {
            remotePostMessage = function(message, target) {
                return parent.postMessage(message, target);
            }
            setLocation = function(newLocation) {
                parent.window.frames[0].location.replace(newLocation);
            }
        } else {
            var messages = [];
            messageUrl = function(message) {
                var id = Math.floor(Math.random() * 100000);
                if (remoteUrl.substr(remoteUrl.length - 1) != '/')
                    remoteUrl += '/';

                return remoteUrl + "poll.html#poll:" + id + ":" + message;
            }

            remotePostMessage = function(message, target) {
                if (message.indexOf(':') >= 0) {
                    // Check to replace a similar message.
                    var messageType = message.split(':')[0];
                    for (var i = 0; i < messages.length; i++) {
                        var messageI = messages[i];
                        if (messageI.length >= messageType.length && messageI.substr(0, messageType.length) == messageType) {
                            messages[i] = message;
                            return;
                        }
                    }
                }
                messages.push(message);
            }

            setLocation = function(newLocation) {
                if (messages.length == 0)
                    parent.window.frames[0].location.replace(newLocation);
                else {
                    setTimeout(function() {
                        setLocation(newLocation);
                    }, 500);
                }
            }

            var nextMessageTime = new Date();
            setMessage = function() {
                if (messages.length == 0)
                    return;

                var messageTime = new Date();
                if (messageTime < nextMessageTime)
                    return;

                messageTime.setSeconds(messageTime.getSeconds() + 2);
                nextMessageTime = messageTime;

                var message = messages.splice(0, 1)[0];
                var url = messageUrl(message);

                document.getElementById('messageFrame').src = url;
            }

            $(function() {
                var body = document.getElementsByTagName("body")[0],
                    messageIframe = document.createElement("iframe");

                messageIframe.id = "messageFrame";
                messageIframe.name = "messageFrame";
                messageIframe.src = messageUrl('');
                messageIframe.style.display = "none";
                body.appendChild(messageIframe);
                setMessage();
                setInterval(setMessage, 300);
            });
        }
    }

    // If not embedded and we should be, redirect to the embedded version.
    if (!inIframe && !inPopup && !inConnect && remoteUrl != '' && ((inDashboard && forceEmbedDashboard) || (!inDashboard && forceEmbedForum)))
        document.location = remoteUrl + '#' + path;

    if (inIframe) {
        // DO NOT set the parent location if this is a page of embedded comments!!
        if (path != '~' && !isEmbeddedComments)
            remotePostMessage('location:' + path, '*');

        // Unembed if in the dashboard, in an iframe, and not forcing dashboard embed
        if (inDashboard && !forceEmbedDashboard)
            remotePostMessage('unembed', '*');

        setHeight = function(explicitHeight) {
            // Offset height can be influenced by CSS styling, like height. A
            // height of 0 is likely due to a theme modifying it, such as setting
            // html,body{height:100%;}. Counter this by defining an auto height.
            document.body.style.cssText += 'height:auto !important;';

            var newHeight = explicitHeight > 0 ? explicitHeight : document.body.offsetHeight;
            if (newHeight > minHeight && newHeight != currentHeight) {
                currentHeight = newHeight;
                remotePostMessage('height:' + currentHeight, '*');
            }
        }

        setInterval(setHeight, 300);

        // Simulate a page unload when popups are opened (so they are scrolled into view).
        $('body').bind('popupReveal', function() {
            remotePostMessage('scrollto:' + $('div.Popup').offset().top, '*');
        });

        $(window).unload(function() {
            remotePostMessage('unload', '*');
        });

        // hijack all anchors to see if they should go to "top" or be within the
        // embed (ie. are they in Vanilla or not?)
        $(document).on('click', 'a', function() {
            var href = $(this).attr('href');
            if (!href)
                return;

            var isHttp = href.substr(0, 7) == 'http://' || href.substr(0, 8) == 'https://',
                noTop = $(this).hasClass('SignOut') || $(this).hasClass('NoTop');

            if ((isHttp && href.substr(0, webroot.length) != webroot) || $(this).hasClass('js-extern')) {
                // Make sure the social sign in links are opened within the topmost
                // window instead of a new window, otherwise forced embed problem.
                var target = ($(this).closest('.Message').length)
                    ? '_blank'
                    : '_top';

                $(this).attr('target', target);
            } else if (isEmbeddedComments) {
                // If clicking a pager link, just follow it.
                if ($(this).parents('.Pager').length > 0)
                    noTop = true;

                // Target the top of the page if clicking an anchor in a list of embedded comments
                if (!noTop)
                    $(this).attr('target', '_top');

                // Change the post-registration target to the page that is currently embedded.
                if ($(this).parents('.CreateAccount').length > 0) {
                    // Examine querystring parameters for a target & replace it with the container page
                    $(this).attr('target', '_top');
                    var href = $(this).attr('href');
                    var targetIndex = href.indexOf('Target=');
                    if (targetIndex > 0) {
                        var target = href.substring(targetIndex + 7);
                        var afterTarget = '';
                        if (target.indexOf('&') > 0)
                            afterTarget = target.substring(target.indexOf('&'));

                        $(this).attr('href', href.substring(0, targetIndex + 7)
                        + encodeURIComponent(gdn.definition('vanilla_url', ''))
                        + afterTarget);
                    }
                }
                return;
            }
        });
    }


    $('#Form_Body').click(function() {
        $('.SignInPopup').click();
    });
});
