var discussionTagging = {
    start: function($element) {
        var $button = $element.find('form :submit');
        if ($button) {
            var $form = $element.find('form');
            var $tagsInput = $element.find("#Form_Tags");

            $form.bind('BeforeDiscussionSubmit', function (e, $form, $button) {
                $tagsInput.triggerHandler('BeforeSubmit', [$form]);
            });

            var tags;
            var dataTags = $tagsInput.data('tags');
            if (dataTags) {
                tags = [];
                if ($.isPlainObject(dataTags)) {
                    for (var tagID in dataTags) {
                        tags.push({name: dataTags[tagID], id: tagID});
                    }
                }
            } else {
                tags = $tagsInput.val();
                if (tags && tags.length) {
                    tags = tags.split(",");

                    for (i = 0; i < tags.length; i++) {
                        tags[i] = {id: tags[i], name: tags[i]};
                    }
                } else {
                    tags = [];
                }
            }

            var tagSearch = gdn.definition('TaggingSearchUrl', gdn.url('tags/search'));
            var tagAdd = gdn.definition('TaggingAdd', false);
            var maxTags = gdn.definition('MaxTagsAllowed', false);

            $tagsInput.tokenInput(tagSearch, {
                hintText: gdn.definition("TagHint", "Start to type..."),
                searchingText: '', // search text gives flickery ux, don't like
                searchDelay: 300,
                animateDropdown: false,
                minChars: 1,
                maxLength: 25,
                prePopulate: tags,
                dataFields: ["#Form_CategoryID"],
                allowFreeTagging: tagAdd,
                tokenLimit: maxTags,
                zindex: 3000,
                allowTabOut: true
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
                $tagsInput.tokenInput('add', {id: tag, name: $(this).text()});
                return false;
            });
        }
    }
}

$(document).on('contentLoad', function(e) {
    if (e.target.id === 'DiscussionForm') {
        discussionTagging.start($('.FormWrapper', e.target));
    } else {
        var elementID = '#DiscussionForm';
        if ($(e.target).find('#DiscussionAddTagForm').length) {
            elementID = '#DiscussionAddTagForm';
        }
        discussionTagging.start($(elementID, e.target));
    }
});
