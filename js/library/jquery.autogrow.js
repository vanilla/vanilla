/* 
 * Auto Expanding Text Area (1.2.2)
 * by Chrys Bader (www.chrysbader.com)
 * chrysb@gmail.com
 *
 * Special thanks to:
 * Jake Chapa - jake@hybridstudio.com
 * John Resig - jeresig@gmail.com
 *
 * Copyright (c) 2008 Chrys Bader (www.chrysbader.com)
 * Licensed under the GPL (GPL-LICENSE.txt) license. 
 *
 *
 * NOTE: This script requires jQuery to work.  Download jQuery at www.jquery.com
 *
 */
 
jQuery(document).ready(function(jQuery) {
        
   var self = null;
 
   jQuery.fn.autogrow = function(o)
   {   
      return this.each(function() {
         new jQuery.autogrow(this, o);
      });
   };

   /**
    * The autogrow object.
    *
    * @constructor
    * @name jQuery.autogrow
    * @param Object e The textarea to create the autogrow for.
    * @param Hash o A set of key/value pairs to set as configuration properties.
    * @cat Plugins/autogrow
    */
   
   jQuery.autogrow = function (e, o)
   {
      this.options            = o || {};
      this.dummy              = null;
      this.interval           = null;
      this.line_height        = this.options.lineHeight || parseInt(jQuery(e).css('line-height'));
      this.min_height         = this.options.minHeight || parseInt(jQuery(e).css('min-height'));
      this.max_height         = this.options.maxHeight || parseInt(jQuery(e).css('max-height'));;
      this.textarea           = jQuery(e);
      
      if(this.line_height == NaN)
        this.line_height = 0;
      
      // Only one textarea activated at a time, the one being used
      this.init();
   };
   
   jQuery.autogrow.fn = jQuery.autogrow.prototype = {
      autogrow: '1.2.2'
   };

   jQuery.autogrow.fn.extend = jQuery.autogrow.extend = jQuery.extend;
   
   jQuery.autogrow.fn.extend({
                   
      init: function() {         
         var self = this;         
         this.textarea.css({overflow: 'hidden', display: 'block'});
         this.textarea.bind('focus', function() { self.startExpand() } ).bind('blur', function() { self.stopExpand() });
         this.checkExpand();   
      },
                   
      startExpand: function() {            
         var self = this;
         this.interval = window.setInterval(function() {self.checkExpand()}, 400);
      },
      
      stopExpand: function() {
         clearInterval(this.interval);   
      },
      
      checkExpand: function() {
      
         // Do some faster checks up here to avoid the labor intensive string copying of the real
         // height difference checks.
      
         var current_chars = this.textarea.val().length;
         if (this.last_chars == undefined) this.last_chars = current_chars;
         if (this.char_diff == undefined) this.cumulative_char_diff = 0;
         var iteration_char_difference = current_chars - this.last_chars;
         this.last_chars = current_chars;
         this.cumulative_char_diff += iteration_char_difference;

         var absolute_char_diff = Math.abs(this.char_diff);
         var height_difference = Math.abs(this.textarea.attr('scrollHeight') - this.textarea.attr('clientHeight'));
         if (!height_difference && absolute_char_diff < 10) return;
         
         // If we get here, resizing is probably needed, so do the real height check
         
         if (this.dummy == null)
         {
            this.dummy = jQuery('<div></div>');
            this.dummy.css({
                                    'font-size'  : this.textarea.css('font-size'),
                                    'font-family': this.textarea.css('font-family'),
                                    'width'      : this.textarea.css('width'),
                                    'padding-left'  : this.textarea.css('padding-left'),
                                    'padding-right' : this.textarea.css('padding-right'),
                                    'padding-top'   : this.textarea.css('padding-top'),
                                    'padding-bottom': this.textarea.css('padding-bottom'),
                                    'line-height': this.line_height + 'px',
                                    'overflow-x' : 'hidden',
                                    'position'   : 'absolute',
                                    'top'        : 0,
                                    'left'       : -9999
                                    }).appendTo('body');
         }
         
         // Strip HTML tags
         var html = this.textarea.val().replace(/(<|>)/g, '');
         
         // IE is different, as per usual
         if (jQuery.browser.msie)
         {
            html = html.replace(/\n/g, '<BR>new');
         }
         else
         {
            html = html.replace(/\n/g, '<br>new');
         }
         
         if (this.dummy.html() != html)
         {
            this.dummy.html(html);   
            
            if (this.max_height > 0 && (this.dummy.height() + this.line_height > this.max_height))
            {
               this.textarea.css('overflow-y', 'auto');
            }
            else
            {
               this.textarea.css('overflow-y', 'hidden');
               if (this.textarea.height() < this.dummy.height() + this.line_height || (this.dummy.height() < this.textarea.height()))
               {
                  this.textarea.css({height: (this.dummy.height() + this.line_height) + 'px'});
               }
               //this.textarea.focus();
            }
         }
         
         this.cumulative_char_diff = 0;
      }
                   
    });
});