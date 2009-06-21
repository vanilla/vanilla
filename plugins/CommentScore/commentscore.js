jQuery(document).ready(function($) {
   
// Handle bookmark button clicks   
$('.CommentScore a').click(function() {
   var $btn = $(this);
   
   // Create an animated number.
   var inc = $btn.attr('title');
   var animate = '<div class="Animate">' + inc + '</div>';
   var $animate = $btn.after(animate).next();
   offset = $btn.offset();
      
   $animate
      .css('top', offset.top)
      .css('left', offset.left)
      .animate({ top: "-=25px", fontSize: "+=4px", left: "-=2px" }, "slow")
      .fadeOut("slow", function() { $animate.remove(); });
   
   $.ajax({
      type: "POST",
      url: $btn.attr('href'),
      data: 'DeliveryType=4&DeliveryMethod=2',
      dataType: 'json',
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         // Popup the error
         $.popup({}, textStatus);
      },
      success: function(json) {
         var $parent = $btn.parent();
         $('span.Score', $parent).text(json.SumScore);
      }
   });
   return false;
});
   
});