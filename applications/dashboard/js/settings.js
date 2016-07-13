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
                container.htmlTrigger(data);
            },
            error: function() {
                container.removeClass('Loading');
                container.text('Failed to load feed.');
            }
        });
    };

    lists.addClass('Loading');
    var newsUrl = gdn.definition("DashboardNewsFeed", "/utility/getfeed/news/5/extended");
    var releasesUrl = gdn.definition("DashboardReleasesFeed", "/utility/getfeed/releases/5/extended");
    if (newsUrl) {
        loadFeed(newsColumn, newsUrl);
    }
    if (releasesUrl) {
        loadFeed(releasesColumn, releasesUrl);
    }
});
