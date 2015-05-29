(function(window, $, Vanilla) {
    // Check to see if we are even embedded.
    if (window.top == window.self)
        return;

    // Call a remote function in the parent.
    Vanilla.parent.callRemote = function(func, args, success, failure) {
        window.parent.callRemote(func, args, success, failure);
    };

    var minHeight = 0;
    Vanilla.parent.adjustPopupPosition = function(pos) {
        var height = document.body.offsetHeight || document.body.scrollHeight;

        // Fix windows that are too small for the popup.
        if (height < $('div.Popup').outerHeight()) {
            height = minHeight = $('div.Popup').outerHeight();
            Vanilla.parent.setHeight();
        }

        var bottom0 = height - (pos.top + pos.height);
        if (bottom0 < 0)
            bottom0 = 0;

        // Move the inform messages.
        $('.InformMessages').animate({bottom: bottom0});

        if (height < pos.height) {
            pos.height = height;
        }

        // Move the popup.
        $('div.Popup').each(function() {
            var newTop = pos.top + (pos.height - $(this).height()) / 2.2;
            if (newTop < 0) {
                newTop = 0;
            }
            $(this).animate({top: newTop});
        });
    }
    $(document).on('informMessage popupReveal', function() {
        Vanilla.parent.callRemote('getScrollPosition', [], Vanilla.parent.adjustPopupPosition);
    });

    Vanilla.parent.signout = function() {
        $.post('/entry/signout.json');
    };

    Vanilla.scrollTo = function(q) {
        var top = $(q).offset().top;
        Vanilla.parent.callRemote('scrollTo', top);
        return false;
    };

    Vanilla.urlType = function(url) {
        var regex = /^#/;
        if (regex.test(url))
            return 'hash';

        // Test for an internal link with no domain.
        regex = /^(https?:)?\/\//i;
        if (!regex.test(url))
            return 'internal';

        // Test for the same domain.
        regex = new RegExp("//" + location.host + "($|[/?#])");
        if (regex.test(url))
            return 'internal';

        // Test for a subdomain.
        var parts = location.host.split(".");
        if (parts.length > 2)
            parts = parts.slice(parts.length - 2);
        var domain = parts.join(".");

        regex = new RegExp("//.+\\." + domain + "($|[/?#])");
        if (regex.test(url))
            return "subdomain";

        return "external";
    };

    var currentHeight = null;
    Vanilla.parent.setHeight = function() {
        // Set the height of the iframe based on vanilla.
        var height = document.body.offsetHeight || document.body.scrollHeight;

        if (height < minHeight) {
            height = minHeight;
        }

        if (height != currentHeight) {
            currentHeight = height;
//         console.log('setHeight: ' + height);
            Vanilla.parent.callRemote('height', height);
        }
    }

    $(window).load(function() {
        Vanilla.parent.setHeight();
        Vanilla.parent.callRemote('notifyLocation', window.location.href);

        setInterval(Vanilla.parent.setHeight, 300);
    });

    $(window).unload(function() {
        window.parent.hide();
    });

    $(document).on('click', 'a', function(e) {
        var href = $(this).attr('href');
        if (!href)
            return;

        switch (Vanilla.urlType(href)) {
            case 'subdomain':
                $(this).attr('target', '_top');
                break;
            case 'external':
                $(this).attr('target', '_blank');
                break;
            case 'internal':
                $(this).attr('target', '');
                break;
        }
    });

    $(window).unload(function() {
        Vanilla.parent.callRemote('scrollTo', 0);
    });
})(window, jQuery, Vanilla);

jQuery(document).ready(function($) {
    if (window.top == window.self)
        return;

    Vanilla.parent.setHeight();
    window.parent.show();

    $('body').addClass('Embedded');
});
