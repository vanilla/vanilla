
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
   
   $('div.twitter-card').each(function(i, el){
      var card = $(el);
      var tweetUrl = card.attr('data-tweeturl');
      var tweetID = card.attr('data-tweetid');
      var cardref = card.get(0);
      
      twttr.widgets.createTweet(
         tweetID,
         cardref,
         function(el){ card.children('a').first().css('display','none'); },
         {
            conversation: "none",
            align: "center"
         }
      );
      
   });
   
});