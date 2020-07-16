jQuery(document).ready(function () {
    var SitemapsBuildURL = gdn.url('/plugin/sitemaps/build');
    jQuery.ajax({
        url: SitemapsBuildURL,
        type: 'POST'
    });
});
