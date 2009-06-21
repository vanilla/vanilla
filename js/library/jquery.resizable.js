/*
   This is a modified version of Ryan O'Dell's jQuery TextAreaResizer
   plugin. Original credits by Ryan below:
  
   jQuery TextAreaResizer plugin
   Created on 17th January 2008 by Ryan O'Dell 
   Version 1.0.4
   
   Converted from Drupal -> textarea.js
   Found source: http://plugins.jquery.com/misc/textarea.js
   $Id: textarea.js,v 1.11.2.1 2007/04/18 02:41:19 drumm Exp $
*/

(function($) {
   
   $.resizable = function(options) {
      $.resizable.settings = $.extend({}, $.resizable.settings, options);
      var el; // The element being resized
      var staticOffset;
      var lastMousePos;
   }
   
   $.resizable.settings = {
      minHeight: 32, // Minimum height for the element
      maxHeight: 0, // 0 is unlimited
      gripPosition: 'bottom', // Where should the grip appear? top or bottom
      cssClass: 'Resizable', // The css class to apply to the div that is wrapped around the element being manipulated.
      onEndDrag: function(){} // Function to be called when dragging finishes.
   }
   
   $.resizable.init = function() {
      $.resizable.el = null;
      $.resizable.staticOffset = null;
      $.resizable.lastMousePos = 0;
   }   

   $.fn.resizable = function(options) {
      $.resizable.settings = $.extend({}, $.resizable.settings, options);
      var settings = $.resizable.settings;
      $.resizable.init();
      
      return this.each(function() {
          $.resizable.el = $(this).addClass('Processed');
          $.resizable.staticOffset = null;

         // 18-01-08 jQuery bind to pass data element rather than direct mousedown - Ryan O'Dell
         // When wrapping the text area, work around an IE margin bug.  See:
         // http://jaspan.com/ie-inherited-margin-bug-form-elements-and-haslayout
         if (settings.gripPosition == 'top') {
            $(this).wrap('<div class="' + settings.cssClass + '"><span></span></div>').parent().prepend(
                  $('<div class="Grip"></div>').bind("mousedown",{element: this} , $.resizable.startDrag)
               );
         } else {
            $(this).wrap('<div class="' + settings.cssClass + '"><span></span></div>').parent().append(
                  $('<div class="Grip"></div>').bind("mousedown",{element: this} , $.resizable.startDrag)
               );
         }

          var grippie = $('div.Grip', $(this).parent())[0];
          grippie.style.marginRight = (grippie.offsetWidth - $(this)[0].offsetWidth) +'px';
      });
   };
   
   $.resizable.startDrag = function(e) {
      $.resizable.el = $(e.data.element);
      $.resizable.el.blur();
      $.resizable.lastMousePos = $.resizable.mousePosition(e).y;
      if ($.resizable.settings.gripPosition == 'top') {
         $.resizable.staticOffset = $.resizable.el.height() + $.resizable.lastMousePos;
         var maxHeight = e.clientY + $.resizable.el.height() - 100;
         if ($.resizable.settings.maxHeight == 0 || $.resizable.settings.maxHeight > maxHeight)
            $.resizable.settings.maxHeight = maxHeight;
      } else {
         $.resizable.staticOffset = $.resizable.el.height() - $.resizable.lastMousePos;
      }
         
      $.resizable.el.css('opacity', 0.5);
      $(document).mousemove($.resizable.performDrag).mouseup($.resizable.endDrag);
      return false;
   }

   $.resizable.performDrag = function(e) {
      var thisMousePos = $.resizable.mousePosition(e).y;
      var mousePos = 0;
      if ($.resizable.settings.gripPosition == 'top') {
         mousePos = $.resizable.staticOffset - thisMousePos;
      } else {
         mousePos = $.resizable.staticOffset + thisMousePos;
      }
         
      if ($.resizable.lastMousePos >= thisMousePos)
         mousePos -= 5;

      $.resizable.lastMousePos = thisMousePos;
      mousePos = Math.max($.resizable.settings.minHeight, mousePos);
      if ($.resizable.settings.maxHeight > 0)
         mousePos = Math.min($.resizable.settings.maxHeight, mousePos);
         
      $.resizable.el.height(mousePos + 'px');
      if (mousePos < $.resizable.settings.minHeight)
         $.resizable.endDrag(e);

      return false;
   }

   $.resizable.endDrag = function(e) {
      $(document).unbind('mousemove', $.resizable.performDrag).unbind('mouseup', $.resizable.endDrag);
      $.resizable.el.css('opacity', 1);
      $.resizable.el.focus();
      $.resizable.settings.onEndDrag();
      $.resizable.init();
   }

   $.resizable.mousePosition = function(e) {
      return {
         x: e.clientX + document.documentElement.scrollLeft,
         y: e.clientY + document.documentElement.scrollTop
      };
   };
})(jQuery);