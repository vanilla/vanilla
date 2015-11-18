var discussionTagging = {

    start: function($page) {
        var btn = $page.find('div#DiscussionForm form :submit');
        var parent = $(btn).parents('div#DiscussionForm');
        var frm = $(parent).find('form');

        frm.bind('BeforeDiscussionSubmit', function (e, frm, btn) {
            var taglist = $(frm).find('input#Form_Tags');
            taglist.triggerHandler('BeforeSubmit', [frm]);
        });

        var tags;
        var data_tags = $page.find("#Form_Tags").data('tags');

        if (data_tags) {
            tags = [];
            if (jQuery.isPlainObject(data_tags)) {
                for (id in data_tags) {
                    tags.push({id: id, name: data_tags[id]});
                }
            }
        } else {
            tags = $page.find("#Form_Tags").val();
            if (tags && tags.length) {
                tags = tags.split(",");

                for (i = 0; i < tags.length; i++) {
                    tags[i] = {id: tags[i], name: tags[i]};
                }
            } else {
                tags = [];
            }
        }

        var TagSearch = gdn.definition('PluginsTaggingSearchUrl', gdn.url('plugin/tagsearch'));
        var TagAdd = gdn.definition('PluginsTaggingAdd', false);

        $page.find("#Form_Tags").tokenInput(TagSearch, {
            hintText: gdn.definition("TagHint", "Start to type..."),
            searchingText: '', // search text gives flickery ux, don't like
            searchDelay: 300,
            animateDropdown: false,
            minChars: 1,
            maxLength: 25,
            prePopulate: tags,
            dataFields: ["#Form_CategoryID"],
            allowFreeTagging: TagAdd,
            zindex: 3000
        });

        // Show available link
        $page.on('click', '.ShowTags a', function () {
            $page.find('.ShowTags a').hide();
            $page.find('.AvailableTags').show();
            return false;
        });

        // Use available tags
        $page.on('click', '.AvailableTag', function () {
            //$(this).hide();
            var tag = $(this).attr('data-id');
            $page.find("#Form_Tags").tokenInput('add', {id: tag, name: $(this).text()});
            return false;
        });
    }
}

jQuery(document).ready(function($) {
    $('#DiscussionForm').each(function() {
        discussionTagging.start($(this));
    });
});
