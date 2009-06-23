jQuery(document).ready(function($) {
   
// Handle bookmark button clicks   
$('.CommentScore a').click(function() {
   var $btn = $(this);
   
   // Create an animated number.
   var inc = $btn.attr('title');
   var animate = '<div class="Animate">' + inc + '</div>';
   var $animate = $btn.after(animate).next();
   offset = $btn.offset();
   
   var topAnim = (inc < 0 ? "+=35px" : "-=35px");
      
   $animate
      .css('top', offset.top)
      .css('left', offset.left)
      .animate({ top: topAnim, fontSize: "+=4px", left: "-=2px" }, "slow")
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
         
         var setLink = function(score, query) {
            $element = $(query, $parent);
            
            if((score == 0 && $element.attr('href') == '') || (score != 0 && $element.attr('href') != ''))
               return;
            
            if(score == 0) {
               $element.attr('href2', $element.attr('href'));
               $element.attr('href', '');
               $element.attr('title', '');
               $element.addClass('Disabled');
            } else {
               $element.attr('href', $element.attr('href2'));
               $element.attr('title', score);
               $element.removeClass('Disabled');
            }
         }
         
         setLink(json.Inc[-1], 'a.Neg');
         setLink(json.Inc[1], 'a.Pos');
      }
   });
   return false;
});
   
});