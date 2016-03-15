/**
 *
 */

var SpoilersPlugin = {
   FindAndReplace: function() {
      jQuery('div.UserSpoiler').each(function(i, el) {
         SpoilersPlugin.ReplaceSpoiler(el);
      });
   },

   ReplaceComment: function(Comment) {
      jQuery(Comment).find('div.UserSpoiler').each(function(i,el){
         SpoilersPlugin.ReplaceSpoiler(el);
      },this);
   },

   /**
    * ReplaceSpoiler: Add the spoiler label to the comment text.
    * Add Gdn::Controller()->addDefinition('show', 'display') in the class.themehooks.php file of your theme
    * This plugin needs to be reworked to avoid needing to do this.
    */
   ReplaceSpoiler: function(Spoiler) {
      // Don't re-event spoilers that are already 'on'
      if (Spoiler.SpoilerFunctioning) return;
      Spoiler.SpoilerFunctioning = true;

      // Extend object with jQuery
      Spoiler = jQuery(Spoiler);
      var SpoilerTitle = Spoiler.find('div.SpoilerTitle').first();
      var SpoilerButton = document.createElement('input');
      SpoilerButton.type = 'button';
      SpoilerButton.value = gdn.definition('show', 'show');
      SpoilerButton.className = 'SpoilerToggle';
      SpoilerTitle.append(SpoilerButton);
      Spoiler.on('click', 'input.SpoilerToggle', function(event) {
         event.stopPropagation();
         SpoilersPlugin.ToggleSpoiler(jQuery(event.delegateTarget), jQuery(event.target));
      });
   },

   ToggleSpoiler: function(Spoiler, SpoilerButton) {
      var ThisSpoilerText = Spoiler.find('div.SpoilerText').first();
      var ThisSpoilerStatus = ThisSpoilerText.css('display');
      var NewSpoilerStatus = (ThisSpoilerStatus == 'none') ? 'block' : 'none';
      ThisSpoilerText.css('display',NewSpoilerStatus);

      if (NewSpoilerStatus == 'none')
         SpoilerButton.val(gdn.definition('show', 'show'));
      else
         SpoilerButton.val(gdn.definition('hide', 'hide'));
   }
};

// Events!

jQuery(document).ready(function(){
   SpoilersPlugin.FindAndReplace();
});

jQuery(document).on('CommentPagingComplete CommentAdded MessageAdded PreviewLoaded popupReveal', function() {
   SpoilersPlugin.FindAndReplace();
});
