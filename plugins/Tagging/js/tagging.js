jQuery(document).ready(function($) {
   var btn = $('div#DiscussionForm form :submit');
   var parent = $(btn).parents('div#DiscussionForm');
   var frm = $(parent).find('form');
   
   frm.bind('BeforeDiscussionSubmit',function(e, frm, btn) {
      var taglist = $(frm).find('input#Form_Tags');
      taglist.triggerHandler('BeforeSubmit',[frm]);
   });
   
   var tags = $("#Form_Tags").val();
   if (tags && tags.length)
      tags = tags.split(",");
   
   var TagSearch = gdn.definition('PluginsTaggingSearchUrl', false);
   var TagAdd = gdn.definition('PluginsTaggingAdd', false);
   $("#Form_Tags").tokenInput(TagSearch, {
      hintText: "Start to type...",
      searchingText: "Searching...",
      searchDelay: 300,
      minChars: 1,
      maxLength: 25,
      prePopulate: tags,
      dataFields: ["#Form_CategoryID"],
      onFocus: function() { $(".Help").hide(); $(".HelpTags").show(); },
      onBlankAdd: function(hidden_input, item) {
         if (!TagAdd) {
            var Tag = $('.token-input-list').find('li:contains("'+item+'")');
            Tag.addClass('not-allowed');
         }
      }
  });
});