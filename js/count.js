(function () {
   var spans = document.getElementsByTagName('span');
   var identifiers = new Array;
   // Loop through all spans looking for vanilla identifiers
   for (i = 0; i < spans.length; i++) {
      for (j = 0; j < spans[i].attributes.length; j++) {
         if (spans[i].attributes[j].name == 'vanilla-identifier') {
            identifiers.push(spans[i].attributes[j].value);
         }
      }
   }
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

      // Include our embed css into the page
      var vanilla_embed_css = document.createElement('link');
      vanilla_embed_css.rel = 'stylesheet';
      vanilla_embed_css.type = 'text/css';
      vanilla_embed_css.href = vanilla_forum_url + (vanilla_forum_url.substring(vanilla_forum_url.length-1) == '/' ? '' : '/') +'applications/dashboard/design/embed.css';
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_embed_css);
   }
   
}());

function vanilla_assign_comment_counts(data) {
   var spans = document.getElementsByTagName('span');
   for (i = 0; i < spans.length; i++) {
      for (j = 0; j < spans[i].attributes.length; j++) {
         if (spans[i].attributes[j].name == 'vanilla-identifier') {
            // Add our counts
            var count = data.CountData[spans[i].attributes[j].value.toString()];
            if (typeof(count) == "undefined")
               count = 0;
               
            if (count == 0)
               spans[i].innerHTML = 'No Comments';
            else
               spans[i].innerHTML = ((count == 1) ? '1 Comment' : count + ' Comments');
               
            // Add our hashtag to the href so we jump to comments
            var href = spans[i].parentNode.href.split('#')[0];
            spans[i].parentNode.href = href+'#vanilla-comments';
         }
      }
   }
}