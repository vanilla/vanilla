/**************************************************************
jQuery / Garden CheckBoxGrid Plugin v1

Usage:
(1) Use Garden's /garden/library/class.form.php to render a
 $Form->OrganizedCheckBoxList();
 
(2) Include this file on the page, and the labels in the table will be
transformed into hyperlinks, allowing the checking/unchecking of all items in a
specific row, column, or the entire table.
**************************************************************/

(function($) {
  $.fn.checkBoxGrid = function(opt) {
   opt = $.extend({
     noOptionsYet: 0
   }, opt);
   
  // Remove the cellpadding on label cells
  $(this).find('th, thead td').css('padding', '0px');
   
  // Handle table heading clicks
  $(this).find('thead th').each(function() {
      var text = $(this).html();
      var anchor = document.createElement('a');
      anchor.onclick = function(sender) {
        var checkboxes = $(this).parents('table').find(":checkbox");
        if ($(this).parents('table').find(":checkbox:first").attr('checked')) {
          checkboxes.removeAttr('checked');
        } else {
          checkboxes.attr('checked', 'checked');
        }
        return false;
      }
      anchor.innerHTML = text;
      anchor.href = '#';
      $(this).html(anchor);
  });

  // Handle row heading clicks
  $(this).find('tbody th').each(function() {
      var text = $(this).html();
      var anchor = document.createElement('a');
      anchor.onclick = function(sender) {
        var checkBoxes = $(this).parents('tr').find(":checkbox");
        if (checkBoxes.length > 0) {
          if ($(checkBoxes[0]).attr('checked')) {
            checkBoxes.removeAttr('checked');
          } else {
            checkBoxes.attr('checked', 'checked');
          }
        }
        return false;
      }
      anchor.innerHTML = text;
      anchor.href = '#';
      $(this).html(anchor);
  });
  
  // Handle column heading clicks
  $(this).find('thead td').each(function() {
      var columnIndex = $(this).attr('cellIndex');
      var columnCount = $(this).siblings('td').length;
      var text = $(this).html();
      var anchor = document.createElement('a');
      anchor.onclick = function(sender) {
        // alert('columns: ' + columnCount);
        var rows = $(this).parents('table').find('tbody tr');
        var checked = false;
        var found = false;
        var checkbox = false;
        for (i = 0; i < rows.length; i++) {
          checkbox = $(rows[i]).find('td:eq(' + (columnIndex-1) + ')').find(":checkbox");
          if (!found && checkbox.length > 0) {
            found = true;
            checked = $(checkbox).attr('checked');
          }
          
          if (checked) {
            checkbox.removeAttr('checked');
          } else {
            checkbox.attr('checked', 'checked');
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
  $('table.CheckBoxGrid').checkBoxGrid();
});