(function () {
   var anchors = document.getElementsByTagName('a');
   var identifiers = new Array;
   // Loop through all anchors looking for vanilla identifiers
   for (i = 0; i < anchors.length; i++) {
      for (j = 0; j < anchors[i].attributes.length; j++) {
         if (anchors[i].attributes[j].name == 'vanilla-identifier') {
            identifiers.push(anchors[i].attributes[j].value);
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
   var anchors = document.getElementsByTagName('a');
   var tpl = '<span class="vanilla-count-button"><span class="vanilla-count-text">Comments</span><span class="vanilla-count"><span class="vanilla-bubble-arrow"></span>{count}</span></span>';
   for (i = 0; i < anchors.length; i++) {
      for (j = 0; j < anchors[i].attributes.length; j++) {
         if (anchors[i].attributes[j].name == 'vanilla-identifier') {
            // Add our css class to the anchor for styling
            var cssClass = (anchors[i].className + ' vanilla-comment-count-anchor').trim();
            anchors[i].className = cssClass;
            // Add our button html
            var count = data.CountData[anchors[i].attributes[j].value.toString()];
            if (typeof(count) == "undefined")
               count = 0;

            anchors[i].innerHTML = tpl.replace('{count}', count);
            // Add our hashtag to the href so we jump to comments
            var href = anchors[i].href.split('#')[0];
            anchors[i].href = href+'#vanilla-comments';
         }
      }
   }
}