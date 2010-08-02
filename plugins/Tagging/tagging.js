jQuery(document).ready(function($) {
   var btn = $('div#DiscussionForm form :submit');
   var parent = $(btn).parents('div#DiscussionForm');
   var frm = $(parent).find('form');
   
   frm.bind('BeforeDiscussionSubmit',function(e, frm, btn) {
      var taglist = $(frm).find('input#Form_Tags');
      taglist.triggerHandler('BeforeSubmit',[frm]);
   });
});