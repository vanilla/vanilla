/*
This is a highly modified version of the Facebox plugin for jQuery by Chris
Wanstrath. Original Credits:
 
Facebox (for jQuery)
version: 1.0 (12/19/2007)
@requires jQuery v1.2 or later
Examples at http://famspam.com/facebox/
Licensed under the MIT: http://www.opensource.org/licenses/mit-license.php
Copyright 2007 Chris Wanstrath [ chris@ozmm.org ]
*/

(function($) {

  // Allows generating a popup by jQuery.popup('contents')
  $.popup = function(options, data) {
    var settings = $.extend({}, $.popup.settings, options);
    $.popup.init(settings)
    if (!settings.confrm)
      $.popup.loading(settings)
      
    $.isFunction(data) ? data.call() : $.popup.reveal(settings, data)
  }

  $.fn.popup = function(options) {
    // Merge the two settings arrays into a central data store
    var settings = $.extend({}, $.popup.settings, options);
    var sender = this;

    this.live('click', function() {
      settings.sender = this;
      $.popup.init(settings);
      if (!settings.confirm)
        $.popup.loading(settings);

      var target = $.popup.findTarget(settings);
      if (settings.confirm) {
        // Bind to the "Okay" button click
        $('#'+settings.popupId+' .Okay').focus().click(function() {
          if (settings.followConfirm) {
            // follow the target
            document.location = target;
          } else {
            // request the target via ajax
            $.ajax({
                type: "GET",
                url: target,
                data: { 'DeliveryType' : settings.deliveryType, 'DeliveryMethod' : 'JSON' },
                dataType: 'json',
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                   $.popup({}, XMLHttpRequest.responseText);
                },
                success: function(json) {
                  json = $.postParseJson(json);
                  
                  $.popup.close(settings);
                  settings.afterConfirm(json, settings.sender);
                  gdn.inform(json.StatusMessage);
                  if (json.RedirectUrl)
                    setTimeout("document.location='" + json.RedirectUrl + "';", 300);

                }
             });
          }
        });
      } else {
        if (target) {
          $.get(target, {'DeliveryType': settings.deliveryType}, function(data) {
            $.popup.reveal(settings, data)
          });
        }
      }
        
      return false;
    });
    
    this.mouseover(function() {
      settings.sender = this;
      if ($.popup.findTarget(settings))
        $(this).addClass(settings.mouseoverClass);
    });
    
    this.mouseout(function() {
      settings.sender = this;
      if ($.popup.findTarget(settings))
        $(this).removeClass(settings.mouseoverClass);
    });
    
    return this;
  }
  
  $.popup.findTarget = function(settings) {
    settings.foundTarget = settings.targetUrl;
    
    // See if the matched element was an anchor. If it was, use the href.
    if (!settings.foundTarget && $(settings.sender).attr('href') != 'undefined') {
      settings.foundTarget = settings.sender.href;
    } else {
      // See if there were any anchors within the matched element.
      // If there are, use the href from the first one.
      if (!settings.foundTarget) {
        var anchor = $(settings.sender).find('a:first');
        if (anchor.length > 0)
          settings.foundTarget = anchor[0].href;
      }
    }
    
    return settings.foundTarget;
  }

  // Close a jquery popup and release escape key bindings
  $.popup.close = function(settings, response) {
    $(document).unbind('keydown.popup');
    $('#'+settings.popupId).trigger('popupClose');
    $('.Overlay').remove();
    
    return false;
  }
    
  $.popup.init = function(settings) {
    // Define a unique identifier for this popup
    var i = 1;
    var popupId = 'Popup';
    while ($('#'+popupId).size() > 0) {
      popupId = 'Popup'+i;
      i++;
    }
    settings.popupId = popupId;
    var popupHtml = '';
    if (!settings.confirm)
      popupHtml = settings.popupHtml;
    else
      popupHtml = settings.confirmHtml;
    
    popupHtml = popupHtml.replace('{popup.id}', settings.popupId);
    
    $('body').append(popupHtml);
    if (settings.containerCssClass != '')
      $('#'+settings.popupId).addClass(settings.containerCssClass);
      
    var pagesize = $.popup.getPageSize();
    $('div.Overlay').css({ height: pagesize[1] });
    
    var pageScroll = $.popup.getPageScroll();
    $('#'+settings.popupId).css({
      top:   pageScroll[1] + ($.popup.getPageHeight() / 10),
      left:   pageScroll[0]
    });
    $('#'+settings.popupId).show();

    $(document).bind('keydown.popup', function(e) {
      if (e.keyCode == 27)
        $.popup.close(settings);
    })    

    if (settings.onUnload) {
      $('#'+settings.popupId).bind('popupClose',function(){
          setTimeout(settings.onUnload,1);
      });
    }

    // Replace language definitions
    if (!settings.confirm) {
      $('#'+settings.popupId+' .Close').click(function() {
        return $.popup.close(settings);
      });
    } else {
      $('#'+settings.popupId+' .Content h1').text(gdn.definition('ConfirmHeading', 'Confirm'));
      $('#'+settings.popupId+' .Content p').text(gdn.definition('ConfirmText', 'Are you sure you want to do that?'));
      $('#'+settings.popupId+' .Okay').val(gdn.definition('Okay', 'Okay'));
      $('#'+settings.popupId+' .Cancel').val(gdn.definition('Cancel', 'Cancel')).click(function() {
        $.popup.close(settings);
      });
    }
  }

  $.popup.loading = function(settings) {
    settings.onLoad(settings);
    if ($('#'+settings.popupId+' .Loading').length == 1)
      return true;
    
    $('#'+settings.popupId+' .Content').empty();
    $('#'+settings.popupId+' .Body').children().hide().end().append('<div class="Loading">&nbsp;</div>');
  }
  
  $.popup.reveal = function(settings, data) {
    // First see if we've retrieved json or something else
    var json = false;
    if (data instanceof Array) {
      json = false;
    } else if (data !== null && typeof(data) == 'object') {
      json = data;
    }

    if (json == false) {
      // This is something other than json, so just put it into the popup directly
      $('#'+settings.popupId+' .Content').append(data);
    } else {
      if (json.StatusMessage)
         gdn.inform(json.StatusMessage);

      formSaved = json['FormSaved'];
      data = json['Data'];
      // mosullivan - need to always reload the data b/c when uninviting ppl
      // we need to reload the invitation table. Is there a reason not to reload
      // the content?
      // if (formSaved == false)
      $('#'+settings.popupId+' .Content').html(data);
    }
    
    $('#'+settings.popupId+' .Loading').remove();
    $('#'+settings.popupId+' .Body').children().fadeIn('normal');
    
    settings.afterLoad();
    
   // Now, if there are any forms in the popup, hijack them if necessary.
   if (settings.hijackForms == true) {
      $('#'+settings.popupId+' form').ajaxForm({
          data: {
             'DeliveryType' : settings.deliveryType,
             'DeliveryMethod' : 'JSON'
         },
         dataType: 'json',
         beforeSubmit: function() {
          settings.onSave(settings) // Notify the user that it is being saved.
         },  
         success: function(json) {
            json = $.postParseJson(json);
            
            if (json.StatusMessage)
               gdn.inform(json.StatusMessage);
         
            if (json.FormSaved == true) {
               if (json.RedirectUrl)
                  setTimeout("document.location='" + json.RedirectUrl + "';", 300);
              
               settings.afterSuccess(settings, json);
               $.popup.close(settings, json);
            } else {
               $.popup.reveal(settings, json) // Setup the form again
            }
         }
      });
    }
    
    // If there is a cancel button in the popup, hide it (the popup has it's own close button)
    $('#'+settings.popupId+' a.Cancel').hide();
    
    return false;
  }
  
  $.popup.settings = {
    targetUrl:        false,        // Use this URL instead of one provided by the matched element?
    confirm:          false,        // Pop up a confirm message?
    followConfirm:    false,        // Follow the confirm url after OK, or request it with ajax?
    afterConfirm:     function(json, sender) {
      // Called after the confirm url has been loaded via ajax
    },                              // Event to fire if the confirm was ajaxed
    hijackForms:      true,         // Hijack popup forms so they are handled in-page instead of posting the entire page back
    deliveryType:     'VIEW',            // Adds DeliveryType=3 to url so Garden doesn't pull the entire page
    mouseoverClass:   'Popable',    // CssClass to be applied to a popup link when hovering
    onSave:           function(settings) {
      if (settings.sender) {
        $('#'+settings.popupId+' .Button:last').attr('disabled', true);
        $('#'+settings.popupId+' .Button:last').after('<span class="Progress">&nbsp;</span>');
      }
    },
    onLoad:           function(settings) {
      // Called before the "loading..." is displayed
    },
    onUnload:         function(settings, response) {
      // Called after the popup is closed
    },
    afterSuccess:     function() {
      // Called after an ajax request resulted in success, and before "close" is called.
    },
    containerCssClass: '',
    popupHtml:       '\
  <div class="Overlay"> \
    <div id="{popup.id}" class="Popup"> \
      <div class="Body"> \
        <div class="Content"> \
        </div> \
        <div class="Footer"> \
          <a href="#" class="Close"><span>×</span></a> \
        </div> \
      </div> \
    </div> \
  </div>',
    confirmHtml:       '\
  <div class="Overlay"> \
    <div id="{popup.id}" class="Popup"> \
      <div class="Body"> \
        <div class="Content"><h1>Confirm</h1><p>Are you sure you want to do that?</p></div> \
        <div class="Footer"> \
          <input type="button" class="Button Okay" value="Okay" /> \
          <input type="button" class="Button Cancel" value="Cancel" /> \
        </div> \
      </div> \
    </div> \
  </div>',
    afterLoad: function() {}
  }

  // Adapted from getPageSize() by quirksmode.com
  $.popup.getPageHeight = function() {
    var windowHeight
    if (self.innerHeight) {   // all except Explorer
      windowHeight = self.innerHeight;
    } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
      windowHeight = document.documentElement.clientHeight;
    } else if (document.body) { // other Explorers
      windowHeight = document.body.clientHeight;
    }   
    return windowHeight
  }
  
  $.popup.getPageSize = function() {
    var xScroll, yScroll;
    if (window.innerHeight && window.scrollMaxY) {   
       xScroll = window.innerWidth + window.scrollMaxX;
       yScroll = window.innerHeight + window.scrollMaxY;
    } else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
       xScroll = document.body.scrollWidth;
       yScroll = document.body.scrollHeight;
    } else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
       xScroll = document.body.offsetWidth;
       yScroll = document.body.offsetHeight;
    }
    var windowWidth, windowHeight;
    if (self.innerHeight) {   // all except Explorer
       if(document.documentElement.clientWidth){
          windowWidth = document.documentElement.clientWidth; 
       } else {
          windowWidth = self.innerWidth;
       }
       windowHeight = self.innerHeight;
    } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
       windowWidth = document.documentElement.clientWidth;
       windowHeight = document.documentElement.clientHeight;
    } else if (document.body) { // other Explorers
       windowWidth = document.body.clientWidth;
       windowHeight = document.body.clientHeight;
    }   
    // for small pages with total height less then height of the viewport
    if(yScroll < windowHeight){
       pageHeight = windowHeight;
    } else { 
       pageHeight = yScroll;
    }
    // for small pages with total width less then width of the viewport
    if(xScroll < windowWidth){   
       pageWidth = xScroll;      
    } else {
       pageWidth = windowWidth;
    }
    arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight);
    return arrayPageSize;
  };  
  
  
  // getPageScroll() by quirksmode.com
  $.popup.getPageScroll = function() {
    var xScroll, yScroll;
    if (self.pageYOffset) {
      yScroll = self.pageYOffset;
      xScroll = self.pageXOffset;
    } else if (document.documentElement && document.documentElement.scrollTop) {    // Explorer 6 Strict
      yScroll = document.documentElement.scrollTop;
      xScroll = document.documentElement.scrollLeft;
    } else if (document.body) {// all other Explorers
      yScroll = document.body.scrollTop;
      xScroll = document.body.scrollLeft;   
    }
    return new Array(xScroll,yScroll) 
  }

})(jQuery);