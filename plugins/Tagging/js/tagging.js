jQuery(document).ready(function($) {
   var btn = $('div#DiscussionForm form :submit');
   var parent = $(btn).parents('div#DiscussionForm');
   var frm = $(parent).find('form');
   
   frm.bind('BeforeDiscussionSubmit',function(e, frm, btn) {
      var taglist = $(frm).find('input#Form_Tags');
      taglist.triggerHandler('BeforeSubmit',[frm]);
   });
   
   var tags = $("#Form_Tags").val();
   if (tags && tags.length) {
      tags = tags.split(",");
      
      for (i = 0; i < tags.length; i++) {
        tags[i] = { id: tags[i], name: tags[i] };
      }
   } else {
       tags = [];
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
   $('.ShowTags a').live('click', function() {
       $('.ShowTags a').hide();
       $('.AvailableTags').show();
       return false;
   });

   // Use available tags
    $('.AvailableTag').live('click', function() {
        //$(this).hide();
        var tag = $(this).attr('data-name');
        
        $("#Form_Tags").tokenInput('add', {id: tag, name: tag});
        return false;
    });
});