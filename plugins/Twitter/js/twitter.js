
/*
 * Perform inline remote lookups for embedded tweets
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 */

window.twttr = (function (d,s,id) {
   var t, js, fjs = d.getElementsByTagName(s)[0];
   if (d.getElementById(id)) return; js=d.createElement(s); js.id=id;
   js.src="https://platform.twitter.com/widgets.js"; fjs.parentNode.insertBefore(js, fjs);
   return window.twttr || (t = { _e: [], ready: function(f){ t._e.push(f) } });
}(document, "script", "twitter-wjs"));

twttr.ready(function(){
   
   $('a.twitter-card').each(function(i, el){
      var link = $(el);
      var tweetUrl = link.attr('href');
      var tweetID = link.attr('data-tweetid');
      
      var card = $('<div class="twitter-card"><a class="tweeturl" href="'+tweetUrl+'">'+tweetUrl+'</span></div>');
      link.replaceWith(card);
      var cardref = card.get(0);
      twttr.widgets.createTweet(
         tweetID,
         cardref,
         null,
         { 
            align: "center"
         }
      );
      
   });
   
});