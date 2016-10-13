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

  // Handle table heading clicks
  $(this).find('thead th').each(function() {
      var text = $(this).html();
      var anchor = document.createElement('a');
      anchor.onclick = function(sender) {
        var checkboxes = $(this).parents('table').find(":checkbox");
        if ($(this).parents('table').find(":checkbox:first").prop('checked')) {
          checkboxes.prop('checked', false);
        } else {
          checkboxes.prop('checked', true);
        }
        $(this).parents('table').trigger('contentLoad');
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
          if ($(checkBoxes[0]).prop('checked')) {
            checkBoxes.prop('checked', false);
          } else {
            checkBoxes.prop('checked', true);
          }
        }
        $(this).parents('tr').trigger('contentLoad');
        return false;
      }
      anchor.innerHTML = text;
      anchor.href = '#';
      $(this).html(anchor);
  });

  // Handle column heading clicks
  $(this).find('thead td').each(function() {
      var columnIndex = $(this).parent().children().index($(this)); //$(this).attr('cellIndex');
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
            checked = $(checkbox).prop('checked');
          }

          if (checked) {
            checkbox.prop('checked', false);
          } else {
            checkbox.prop('checked', true);
          }
        }
        $(this).parents('table').trigger('contentLoad');
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
  $('.js-checkbox-grid').checkBoxGrid();
});
