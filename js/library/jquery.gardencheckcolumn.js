/**************************************************************
jQuery / Garden CheckColumn Plugin v1
**************************************************************/

(function($) {
  $.fn.checkColumn = function(opt) {
   opt = $.extend({
     noOptionsYet: 0
   }, opt);
   
  // Remove the cellpadding on anchor cells
  $(this).find('thead td').css('padding', '0px');
   
  // Handle column heading clicks
  $(this).find('thead td').each(function() {
      var columnIndex = $(this).attr('cellIndex');
      var text = $(this).html();
      var anchor = document.createElement('a');
      anchor.onclick = function(sender) {
        var rows = $(this).parents('table').find('tbody tr');
        var checkbox = false;
        for (i = 0; i < rows.length; i++) {
          checkbox = $(rows[i]).find('td:eq(' + (columnIndex) + ')').find(":checkbox");
          if (checkbox) {
            if ($(checkbox).attr('checked')) {
              checkbox.removeAttr('checked');
            } else {
              checkbox.attr('checked', 'checked');
            }
          }
        }
        return false;
      }
      anchor.innerHTML = text;
      anchor.href = '#';
      $(this).html(anchor);
  });
  
   
   // Return the object for chaining
     return $(this);
  }
})(jQuery);

$(function() {
  $('table.CheckColumn').checkColumn();
});