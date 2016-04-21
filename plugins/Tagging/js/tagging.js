var discussionTagging = {
    start: function($element) {
        var btn = $element.find('div#DiscussionForm form :submit');
        if (btn) {
            var parent = $(btn).parents('div#DiscussionForm');
            var frm = $(parent).find('form');

            frm.bind('BeforeDiscussionSubmit', function (e, frm, btn) {
                var taglist = $(frm).find('input#Form_Tags');
                taglist.triggerHandler('BeforeSubmit', [frm]);
            });

            var tags;
            var data_tags = $element.find("#Form_Tags").data('tags');
            if (data_tags) {
                tags = [];
                if (jQuery.isPlainObject(data_tags)) {
                    for (name in data_tags) {
                        tags.push({id: data_tags[name], name: name});
                    }
                }
            } else {
                tags = $element.find("#Form_Tags").val();
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

            $element.find("#Form_Tags").tokenInput(TagSearch, {
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
            $element.on('click', '.ShowTags a', function () {
                $element.find('.ShowTags a').hide();
                $element.find('.AvailableTags').show();
                return false;
            });

            // Use available tags
            $element.on('click', '.AvailableTag', function () {
                //$(this).hide();
                var tag = $(this).attr('data-id');
                $element.find("#Form_Tags").tokenInput('add', {id: tag, name: $(this).text()});
                return false;
            });
        }
    }
}

$(document).on('contentLoad', function(e) {
    discussionTagging.start($('#DiscussionForm', e.target));
});
