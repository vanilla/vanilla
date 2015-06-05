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

/**
 * Vanilla Forums NOTE:
 * ====================
 *
 * @date Feb.19,2014
 *
 * I (Dane) modified this plugin as it's no longer supported by the author,
 * and ultimately not available by the author. His site is not even up, and
 * it's no longer listed as a plugin on plugins.jquery.com.
 * There was a copy of the plugin found on Github, hosted by someone
 * unrelated, who made a minor tweak five years ago, but it was unofficial.
 * It's safe to say the plugin is now living on its own.
 *
 * I made changes to this plugin for two reasons:
 *
 *    #1 - Autogrow functionality and the manual resizing corner of textareas
 *      (introduced after the creation of this plugin), don't play well
 *      together. If typing text into a manually resized textarea, it will
 *      snap to the current height of the text. There's no purpose to a
 *      manual resize when there is autogrow enabled. Autogrow's purpose is
 *      to show all typed in text and nothing more. This was not a case
 *      coded for, as browsers at the time did not have the ability to
 *      manually resize a textarea. In addition, there was a
 *      related bug filed in TW. This is fixed by setting the `resize`
 *      CSS property to `none` on the textarea.
 *
 *    #2 - Secondly, Firefox and Chrome calculate the height of nodes
 *      differently when a node has the `box-sizing` value of `border-box`.
 *      This causes the topmost text in the textarea to slowly creep out of
 *      view and disappear. This was not an issue when the plugin was created,
 *      as `box-sizing` was not available in any browser at the time.
 *      This is fixed by checking the `box-sizing` value of the textarea and
 *      applying it to the "dummy" node created by the plugin.
 *
 * These changes are noted below.
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
         // Fix #1, adding resize:none
         this.textarea.css({overflow: 'hidden', display: 'block', resize: 'none'});
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
            // Fix #2
            // content-box is default box-sizing value.
            var box_sizing = 'content-box';
            var css_box_sizing = this.textarea.css('box-sizing');
            if (css_box_sizing != 'hundefined') {
               box_sizing = this.textarea.css('box-sizing');

            }

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
                                    'left'       : -9999,
                                    // Fix #2
                                    'box-sizing' : box_sizing
                                    }).appendTo('body');
         }

         // Strip HTML tags
         var html = this.textarea.val().replace(/(<|>)/g, '');

         // IE is different, as per usual
         if (/msie/.test(navigator.userAgent.toLowerCase()))
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