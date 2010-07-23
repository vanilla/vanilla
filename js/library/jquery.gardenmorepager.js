/* 
 * Garden's jQuery MorePager (1.0.0)
 * by Mark O'Sullivan (www.markosullivan.ca)
 * mark@vanillaforums.com
 *
 * Copyright (c) 2009 Mark O'Sullivan (www.markosullivan.ca)
 * Licensed under the GPL v2. 
 *
 *
 * NOTE: This script requires jQuery to work.  Download jQuery at www.jquery.com
 */
 
(function(jQuery) {
        
   var self = null;
   jQuery.fn.morepager = function(o)
   {   
      return this.each(function() {
         new jQuery.morepager(this, o);
      });
   };
   
   /**
    * The morepager object.
    *
    * @constructor
    * @name jQuery.morepager
    * @param Object e The element containing the "more" link.
    * @param Hash o A set of key/value pairs to set as configuration properties.
    * @cat Plugins/morepager
    */
   jQuery.morepager = function (e, o) {
      this.options                  = o || {};
      this.pager_loading_class      = this.options.pagerLoadingClass || 'Loading';
      this.page_container_selector  = this.options.pageContainerSelector || 'undefined';
      this.pager_in_container       = this.options.pagerInContainer || false;
      this.page_container           = jQuery(this.page_container_selector);
      this.pager_row                = jQuery(e);
      this.pager_row_id             = this.pager_row.attr('id');
      this.extra_pager_data         = this.options.extraPagerData || '';
      this.after_page_loaded        = this.options.afterPageLoaded;
      this.init();
   };
   
   jQuery.morepager.fn = jQuery.morepager.prototype = {
      morepager: '1.0.0'
   };
   
   jQuery.morepager.fn.extend = jQuery.morepager.extend = jQuery.extend;
   
   jQuery.morepager.fn.extend({
      init: function() {
         var self = this;
         $('#' + this.pager_row_id + ' a').live('click', function() {
            var anchor = this;
            self.page_source = self.options.pageSource || $(anchor).attr('href');
            self.page(anchor);
            return false;
         });
      },
                   
      page: function(anchor) {
         var self = this;
         $(anchor).html('&nbsp;').addClass(self.pager_loading_class);
         var type = self.pager_row_id.substr(self.pager_row_id.length - 4, self.pager_row_id.length).toLowerCase() == 'more' ? 'more' : 'less';
         $.ajax({
            type: "POST",
            url: self.page_source,
            data: 'DeliveryType=VIEW&DeliveryMethod=JSON&Form/TransientKey=' + gdn.definition('TransientKey', '') + self.extra_pager_data,
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
               json = $.postParseJson(json);
               
               if (self.pager_in_container == true) {
                  if (type == 'more') {
                     self.pager_row.before(json.Data);
                     self.pager_row.before(json.MoreRow);
                  } else {
                     self.pager_row.after(json.Data);
                     self.pager_row.after(json.LessRow);
                  }
               } else {
                  if (type == 'more') {
                     self.page_container.append(json.Data);
                     self.pager_row.before(json.MoreRow);
                  } else {
                     self.page_container.prepend(json.Data);
                     self.pager_row.after(json.LessRow);
                  }
               }
               self.pager_row.remove();
               self.pager_row = $('#' + self.pager_row_id);
               
               if (self.after_page_loaded != null)
                  self.after_page_loaded();
            }
         });
      }
   });
})(jQuery);