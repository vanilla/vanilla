(function () {
   var span_identifiers = vanilla_collect_identifiers('span'),
      a_identifiers = vanilla_collect_identifiers('a');
      
   var identifiers = span_identifiers.concat(a_identifiers);
   if (identifiers.length > 0) {
      // Grab the comment counts
      var vanilla_count_script = document.createElement('script');
      vanilla_count_script.type = 'text/javascript';
      var timestamp = new Date().getTime();

      vanilla_count_script.src = vanilla_forum_url + '/discussions/getcommentcounts.json'
         +'?time='+timestamp
         +'&callback=vanilla_assign_comment_counts'
         +'&vanilla_identifier[]='
         +identifiers.join('&vanilla_identifier[]=');
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_count_script);
   }
}());

function vanilla_collect_identifiers(tagName) {
   var tags = document.getElementsByTagName(tagName);
   var identifiers = new Array;
   // Loop through all spans looking for vanilla identifiers
   for (i = 0; i < tags.length; i++) {
      for (j = 0; j < tags[i].attributes.length; j++) {
         if (tags[i].attributes[j].name == 'vanilla-identifier') {
            identifiers.push(tags[i].attributes[j].value);
         }
      }
   }
   return identifiers;
}

function vanilla_assign_comment_counts(data) {
   vanilla_assign_comment_counts_by_tag(data, 'span');
   vanilla_assign_comment_counts_by_tag(data, 'a');
}

function vanilla_assign_comment_counts_by_tag(data, tagName) {
   if (typeof vanilla_comments_none =="undefined")
      vanilla_comments_none = 'No Comments';

   if (typeof vanilla_comments_singular =="undefined")
      vanilla_comments_singular = '1 Comment';

   if (typeof vanilla_comments_plural =="undefined")
      vanilla_comments_plural = '{num} Comments';

   var tags = document.getElementsByTagName(tagName);
   for (i = 0; i < tags.length; i++) {
      for (j = 0; j < tags[i].attributes.length; j++) {
         if (tags[i].attributes[j].name == 'vanilla-identifier') {
            // Add our counts
            var count = data.CountData[tags[i].attributes[j].value.toString()];
            if (typeof(count) == "undefined")
               count = 0;
               
            if (count == 0)
               tags[i].innerHTML = vanilla_comments_none.replace('{num}', count).replace('[num]', count);
            else {
               tags[i].innerHTML = ((count == 1) ? vanilla_comments_singular.replace('{num}', count).replace('[num]', count) : vanilla_comments_plural.replace('{num}', count).replace('[num]', count));
            }
               
            // Add our hashtag to the href so we jump to comments
            var anchorNode = tagName == 'a' ? tags[i] : tags[i].parentNode;
            if (anchorNode.href) {
               var href = anchorNode.href.split('#')[0];
               anchorNode.href = href+'#vanilla-comments';
            }
         }
      }
   }
}