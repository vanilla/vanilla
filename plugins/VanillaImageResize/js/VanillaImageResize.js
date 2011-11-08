// Shrink large images to fit into message space, and pop into new window when clicked.
// This needs to happen in onload because otherwise the image sizes are not yet known.
jQuery(window).load(function() {

   var toggler = function(t_img, t_width) {
      if (t_img.css('width') == 'auto')
         t_img.css('width',t_width);
      else
         t_img.css('width','auto');
      return false;
   }

   jQuery('div.Message img').each(function(i,img) {
      var img = jQuery(img);
      var container = img.parents('div.Message');
      if (img.width() > container.width()) {
         var smwidth = container.width();

         img.css('width', smwidth).css('cursor', 'pointer');
         img.after('<div class="ImageResized">' + gdn.definition('ImageResized', 'This image has been resized to fit in the page. Click to enlarge.') + '</div>');

         img.next().click(function() {
            return toggler(img, smwidth);
         });
         img.click(function() {
            return toggler(img, smwidth);
         })
      }
   });
});
