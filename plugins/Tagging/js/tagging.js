jQuery(document).ready(function($) {
    var btn = $('div#DiscussionForm form :submit');
    var parent = $(btn).parents('div#DiscussionForm');
    var frm = $(parent).find('form');

    frm.bind('BeforeDiscussionSubmit', function(e, frm, btn) {
        var taglist = $(frm).find('input#Form_Tags');
        taglist.triggerHandler('BeforeSubmit', [frm]);
    });

    var tags;
    var data_tags = $("#Form_Tags").data('tags');
    if (data_tags) {
        tags = [];
        if (jQuery.isPlainObject(data_tags)) {
            for (id in data_tags) {
                tags.push({id: id, name: data_tags[id]});
            }
        }
    } else {
        tags = $("#Form_Tags").val();
        if (tags && tags.length) {
            tags = tags.split(",");

            for (i = 0; i < tags.length; i++) {
                tags[i] = {id: tags[i], name: tags[i]};
            }
        } else {
            tags = [];
        }
    }

    var TagSearch = gdn.definition('PluginsTaggingSearchUrl', false);
    var TagAdd = gdn.definition('PluginsTaggingAdd', false);
    $("#Form_Tags").tokenInput(TagSearch, {
        hintText: gdn.definition("TagHint", "Start to type..."),
        searchingText: '', // search text gives flickery ux, don't like
        searchDelay: 300,
        animateDropdown: false,
        minChars: 1,
        maxLength: 25,
        prePopulate: tags,
        dataFields: ["#Form_CategoryID"],
        allowFreeTagging: TagAdd
    });

    // Show available link
    $(document).on('click', '.ShowTags a', function() {
        $('.ShowTags a').hide();
        $('.AvailableTags').show();
        return false;
    });

    // Use available tags
    $(document).on('click', '.AvailableTag', function() {
        //$(this).hide();
        var tag = $(this).attr('data-id');

        $("#Form_Tags").tokenInput('add', {id: tag, name: $(this).text()});
        return false;
    });
});
