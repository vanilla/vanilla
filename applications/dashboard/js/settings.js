jQuery(document).ready(function($) {

    // Load news & tutorials from Vanilla
    var lists = $('div.Column div.List'),
        newsColumn = $('div.NewsColumn div.List'),
        releasesColumn = $('div.ReleasesColumn div.List');

    loadFeed = function(container, feedUrl) {
        $.ajax({
            type: "GET",
            url: gdn.url(feedUrl),
            success: function(data) {
                container.removeClass('Loading');
                container.html(data);
            },
            error: function() {
                container.removeClass('Loading');
                container.text('Failed to load ' + type + ' feed.');
            }
        });
    };

    lists.addClass('Loading');
    var newsUrl = gdn.definition("DashboardNewsFeed", "/dashboard/utility/getfeed/news/5/normal");
    var releasesUrl = gdn.definition("DashboardReleasesFeed", "/dashboard/utility/getfeed/releases/2/extended");
    if (newsUrl) {
        loadFeed(newsColumn, newsUrl);
    }
    if (releasesUrl) {
        loadFeed(releasesColumn, releasesUrl);
    }
});
