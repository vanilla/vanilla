/* 
 * Garden CheckColumn Plugin (1.1)
 * by Mark O'Sullivan (mark@vanillaforums.com)
 * by Tim Gunter (tim@vanillaforums.com)
 *
 * Copyright (c) 2008 Vanilla Forums, Inc
 * Licensed under the GPL (GPL-LICENSE.txt) license. 
 *
 * NOTE: This script requires jQuery to work.
 * Download jQuery at www.jquery.com
 */

jQuery(document).ready(function($){
   $.fn.checkColumn = function(opt) {
      opt = $.extend({
        noOptionsYet: 0
      }, opt);

      // Remove the cellpadding on anchor cells
      $(this).find('thead td').css('padding', '0px');

      // Handle column heading clicks
      $(this).find('thead td').each(function(i,el) {
         el = $(el);
         var columnIndex = el.prop('cellIndex');
         var text = el.html();
         el.html('');
         
         var anchor = $('<a></a>');
         anchor.click(function(event) {
            var rows = $(el).parents('table').find('tbody tr');
            var checkbox = false;
            rows.each(function(j,row){
               checkbox = $(row).find('td:eq(' + (columnIndex) + ')').find(":checkbox");
               if (checkbox) {
                  if (checkbox.prop('checked')) {
                     checkbox.removeAttr('checked');
                  } else {
                     checkbox.prop('checked', 'checked');
                  }
               }
            })
            return false;
         });
         anchor.html(text);
         anchor.prop('href', '#');
         el.append(anchor);
      });
   
      // Return the object for chaining
      return $(this);
   }
   
   $('table.CheckColumn').checkColumn();
});