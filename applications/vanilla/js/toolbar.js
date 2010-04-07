jQuery(document).ready(function($) {
   
   $.toolbarPopupLoader = function(button, callback) {
      if ($('div.ToolbarPopup').length == 0) {
         $('body').append('<div class="ToolbarPopup" style="display: none;"> \
            <div class="Content"> \
            </div> \
            <input type="hidden" name="ToolbarUrl" />\
         </div>');
      
         // Make the toolbar popup resizable
         $('div.ToolbarPopup div.Content').resizable({
            minHeight: 100,
            gripPosition: 'top',
            onEndDrag: function() {
               // Save the user's preference.
               // var height = $('#CommentForm textarea').height();
               // $.get(combinePaths(definition('WebRoot', ''), 'index.php/garden/utility/set/preference/CommentBoxHeight/'+ height + '/' + definition('TransientKey', '') + '/?DeliveryType=BOOL'));
            }
         });
      }
      
      var inp = $('div.ToolbarPopup input[name=ToolbarUrl]');
      
      // If the button that was pressed is already loaded into the toolbar popup
      if ($(button).attr('href') == $(inp).val()) {
         // hide it
         $('div.ToolbarPopup').slideUp('fast');
         $(inp).val('');
      } else {
         // Otherwise, load it.
         $('div.ToolbarPopup').slideUp('fast', function() {
            // And now reveal it
            $(this).find('div.Content').html('<div class="Loading">&nbsp;</div>');
            $(this).slideToggle('fast');
            callback();
         });
         // Record what url was loaded
         $(inp).val($(button).attr('href'));
      }
   }
   
   // Hijack "bookmarks" clicks & reveal bookmarks in toolbar popup
   $('li.Bookmarks a, li.Drafts a').click(function() {
      var btn = this;
      
      // Define/Add the toolbar popup container to the page
      $.toolbarPopupLoader(btn, function() {
         // Record the url being loaded in the hidden input
         $('div.ToolbarPopup input[name=ToolbarUrl]').val($(btn).attr('href'));
         
         // Get the bookmarks and put them into the new container
         $('div.ToolbarPopup div.Content').load($(btn).attr('href') + '?DeliveryType=VIEW&DeliveryMethod=XHTML');
      });
      
      return false;
   });
   
});