$(document).ready(function() {

    // Get type of URL.
    var urlType = function(url) {
        // Test for starting hash.
        var regex = /^#/;
        if (regex.test(url)) {
            return 'hash';
        }

        // Test for an internal link with no domain.
        regex = /^(https?:)?\/\//i;
        if (!regex.test(url)) {
            return 'internal';
        }

        // Test for the same domain.
        regex = new RegExp("//" + location.host + "($|[/?#])");
        if (regex.test(url)) {
            return 'internal';
        }

        // Test for a subdomain.
        var parts = location.host.split(".");
        if (parts.length > 2) {
            parts = parts.slice(parts.length - 2);
        }
        var domain = parts.join(".");
        regex = new RegExp("//.+\\." + domain + "($|[/?#])");
        if (regex.test(url)) {
            return "subdomain";
        }

        return "external";
    };

    // Assign attributes to links in Messages based on Type.
    // Ultimately this should run on a settings page + definitions.
    $(document).on('click', '.Message a', function (e) {
        var href = $(this).attr('href');
        if (!href)
            return;

        switch (urlType(href)) {
            // case 'subdomain':
            //    $(this).attr('target', '_top');
            //    break;
            case 'external':
                $(this).attr('target', '_blank');
                break;
            // case 'internal':
            //    $(this).attr('target', '');
            //    break;
        }
    });

});