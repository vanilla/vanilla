
/**
 * Global Garden Javascript
 *
 * This file defines the global 'gdn' object which probides convenience methods
 * useful to the application and clientside development.
 * 
 * The file is split into 3 parts:
 * 
 * 1) Definitions - This area runs as soon as the file is loaded, and should be
 * used to defile global objects and methods, but not to execute events unless 
 * their execution order is irrelevant.
 * 
 * 2) Document Ready Event - This area is the global jQuery 'document ready' event and 
 * should be used for code the must run prior to the document being rendered.
 * 
 * 3) Window Load Event - This area is the global jQuery 'document loaded' event and
 * fires once rendering is complete and also resources are downloaded.
 *
 */

/**
 * Global Definitions
 */

// Vanilla library function.
(function(window, $) {

   var Vanilla = function() { };

   Vanilla.fn = Vanilla.prototype;

   if (!window.console)
      window.console = { log: function() {} };

   // Add a stub for embedding.
   Vanilla.parent = function() {};
   Vanilla.parent.callRemote = function(func, args, success, failure) { console.log("callRemote stub: "+func, args); };

   window.Vanilla = Vanilla;

})(window, jQuery);

var gdn = {
   focused: true,
   Libraries: {}
}

// Grab a definition from hidden inputs in the page
gdn.definition = function(definition, defaultVal, set) {
   if (defaultVal == null)
      defaultVal = definition;

   var $def = $('#Definitions #' + definition);
   var def;

   if(set) {
      $def.val(defaultVal);
      def = defaultVal;
   } else {
      def = $def.val();
      if ($def.length == 0)
         def = defaultVal;
   }

   return def;
}

gdn.disable = function(e, progressClass) {
   var href = jQuery(e).attr('href');
   if (href) {
      $.data(e, 'hrefBak', href);
   }
   jQuery(e).addClass(progressClass ? progressClass : 'InProgress').removeAttr('href').attr('disabled', true);
}

gdn.enable = function(e) {
   jQuery(e).attr('disabled', false).removeClass('InProgress');
   var href = $.data(e, 'hrefBak');
   if (href) {
      jQuery(e).attr('href', href);
      $.removeData(e, 'hrefBak');
   }
}

gdn.elementSupports = function(element, attribute) {
   var test = document.createElement(element);
   if (attribute in test)
      return true;
   else
      return false;
}

gdn.querySep = function(url) {
   return url.indexOf('?') == -1 ? '?' : '&';
}

// Generate a random string of specified length
gdn.generateString = function(length) {
   if (length == null)
      length = 5;

   var chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%*';
   var string = '';
   var pos = 0;
   for (var i = 0; i < length; i++) {
      pos = Math.floor(Math.random() * chars.length);
      string += chars.substring(pos, pos + 1);
   }
   return string;
};

// Combine two paths and make sure that there is only a single directory concatenator
gdn.combinePaths = function(path1, path2) {
   if (path1.substr(-1, 1) == '/')
      path1 = path1.substr(0, path1.length - 1);

   if (path2.substring(0, 1) == '/')
      path2 = path2.substring(1);

   return path1 + '/' + path2;
};

gdn.processTargets = function(targets, $elem, $parent) {
   if(!targets || !targets.length)
      return;

   var tar = function(q) {
      switch (q) {
         case '!element':
            return $elem;
         case '!parent':
            return $parent;
         default:
            return q;
      }
   }

   for(i = 0; i < targets.length; i++) {
      var item = targets[i];

      if (jQuery.isArray(item.Target)) {
         $target = jQuery(tar(item.Target[0]), tar(item.Target[1]));
      } else {
         $target = jQuery(tar(item.Target));
      }

      switch(item.Type) {
         case 'AddClass':
            $target.addClass(item.Data);
            break;
         case 'Ajax':
            $.ajax({
               type: "POST",
               url: item.Data
            });
            break;
         case 'Append':
            $target.append(item.Data);
            break;
         case 'Before':
            $target.before(item.Data);
            break;
         case 'After':
            $target.after(item.Data);
            break;
         case 'Highlight':
            $target.effect("highlight", {}, "slow");
            break;
         case 'Prepend':
            $target.prepend(item.Data);
            break;
         case 'Redirect':
            window.location.replace(item.Data);
            break;
         case 'Refresh':
            window.location.reload();
            break;
         case 'Remove':
            $target.remove();
            break;
         case 'RemoveClass':
            $target.removeClass(item.Data);
            break;
         case 'ReplaceWith':
            $target.replaceWith(item.Data);
            break;
         case 'SlideUp':
            $target.slideUp('fast');
            break;
         case 'SlideDown':
            $target.slideDown('fast');
            break;
         case 'Text':
            $target.text(item.Data);
            break;
         case 'Html':
            $target.html(item.Data);
            break;
         case 'Event':
            gdn.event(item.Data).fire(item.Target);
            break;
      }
   }
};
gdn.requires = function(Library) {
   if (!(Library instanceof Array))
      Library = [Library];

   var Response = true;

   jQuery(Library).each(function(i,Lib){
      // First check if we already have this library
      var LibAvailable = gdn.available(Lib);

      if (!LibAvailable) Response = false;

      // Skip any libs that are ready or processing
      if (gdn.Libraries[Lib] === false || gdn.Libraries[Lib] === true)
         return;

      // As yet unseen. Try to load
      gdn.Libraries[Lib] = false;
      var Src = '/js/'+Lib+'.js';
      var head = document.getElementsByTagName('head')[0];
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = Src;
      head.appendChild(script);
   });

   if (Response) gdn.loaded(null);
   return Response;
};
// Convert date fields to datepickers
if ($.fn.datepicker) {
   $('input.DatePicker').datepicker({
      showOn: "focus",
      dateFormat: 'mm/dd/yy'
   });
}


gdn.events = {};
gdn.event = function( id ) {
    var callbacks,
        method,
        topic = id && gdn.events[ id ];
    if ( !topic ) {
        callbacks = jQuery.Callbacks();
        topic = {
            fire: callbacks.fire,
            subscribe: callbacks.add,
            unsubscribe: callbacks.remove
        };
        if ( id ) {
            gdn.events[ id ] = topic;
        }
    }
    return topic;
};

gdn.loaded = function(Library) {
   if (Library != null) 
      gdn.Libraries[Library] = true;

   jQuery(document).trigger('libraryloaded',[Library])
}

gdn.available = function(Library) {
   if (!(Library instanceof Array))
      Library = [Library];

   for (var i = 0; i<Library.length; i++) {
      var Lib = Library[i];
      if (gdn.Libraries[Lib] !== true) return false;
   }
   return true;
}

gdn.url = function(path) {
   if (path.indexOf("//") >= 0)
      return path; // this is an absolute path.

   var urlFormat = gdn.definition("UrlFormat", "/{Path}");

   if (path.substr(0, 1) == "/")
      path = path.substr(1);

   if (urlFormat.indexOf("?") >= 0)
      path = path.replace("?", "&");

   return urlFormat.replace("{Path}", path);
};

gdn.stats = function() {
   // Call directly back to the deployment and invoke the stats handler
   var StatsURL = gdn.url('settings/analyticstick.json');
   var SendData = {
         'TransientKey': gdn.definition('TransientKey'), 
         'Path': gdn.definition('Path'),
         'Args': gdn.definition('Args'),
         'ResolvedPath': gdn.definition('ResolvedPath'),
         'ResolvedArgs': gdn.definition('ResolvedArgs')
      };

   if (gdn.definition('TickExtra', null) != null)
      SendData.TickExtra = gdn.definition('TickExtra');

   jQuery.ajax({
      dataType: 'json',
      type: 'post',
      url: StatsURL,
      data: SendData,
      success: function(json) {
         gdn.inform(json);
      }
   });
}

gdn.setAutoDismiss = function() {
   var timerId = jQuery('div.InformMessages').attr('autodismisstimerid');
   if (!timerId) {
      timerId = setTimeout(function() {
         jQuery('div.InformWrapper.AutoDismiss').fadeOut('fast', function() {
            jQuery(this).remove();


         });
         jQuery('div.InformMessages').removeAttr('autodismisstimerid');
      }, 7000);
      jQuery('div.InformMessages').attr('autodismisstimerid', timerId);
   }
}

// Take any "inform" messages out of an ajax response and display them on the screen.
gdn.inform = function(response) {
   if (!response)
      return false;

   if (!response.InformMessages || response.InformMessages.length == 0)
      return false;

   // If there is no message container in the page, add one
   var informMessages = jQuery('div.InformMessages');
   if (informMessages.length == 0) {
      jQuery('<div class="InformMessages"></div>').appendTo('body');
      informMessages = jQuery('div.InformMessages');
   }
   var wrappers = jQuery('div.InformMessages div.InformWrapper');

   // Loop through the inform messages and add them to the container
   for (var i = 0; i < response.InformMessages.length; i++) {
      css = 'InformWrapper';
      if (response.InformMessages[i]['CssClass'])
         css += ' ' + response.InformMessages[i]['CssClass'];

      elementId = '';
      if (response.InformMessages[i]['id'])
         elementId = response.InformMessages[i]['id'];

      sprite = '';
      if (response.InformMessages[i]['Sprite']) {
         css += ' HasSprite';
         sprite = response.InformMessages[i]['Sprite'];
      }

      dismissCallback = response.InformMessages[i]['DismissCallback'];
      dismissCallbackUrl = response.InformMessages[i]['DismissCallbackUrl'];
      if (dismissCallbackUrl)
         dismissCallbackUrl = gdn.url(dismissCallbackUrl);

      try {
         var message = response.InformMessages[i]['Message'];
         var emptyMessage = message == '';

         // Is there a sprite?
         if (sprite != '')
            message = '<span class="InformSprite '+sprite+'"></span>' + message;

         // If the message is dismissable, add a close button
         if (css.indexOf('Dismissable') > 0)
            message = '<a class="Close"><span>×</span></a>' + message;

         message = '<div class="InformMessage">'+message+'</div>';
         // Insert any transient keys into the message (prevents csrf attacks in follow-on action urls).
         message = message.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
         // Insert the current url as a target for inform anchors
         message = message.replace(/{SelfUrl}/g, document.URL);

         var skip = false;
         for (var j = 0; j < wrappers.length; j++) {
            if (jQuery(wrappers[j]).text() == jQuery(message).text()) {
               skip = true;
            }
         }
         if (!skip) {
            if (elementId != '') {
               jQuery('#'+elementId).remove();
               elementId = ' id="'+elementId+'"';
            }
            if (!emptyMessage) {
               informMessages.prepend('<div class="'+css+'"'+elementId+'>'+message+'</div>');
               // Is there a callback or callback url to request on dismiss of the inform message?
               if (dismissCallback) {
                  jQuery('div.InformWrapper:first').find('a.Close').click(eval(dismissCallback));
               } else if (dismissCallbackUrl) {
                  dismissCallbackUrl = dismissCallbackUrl.replace(/{TransientKey}/g, gdn.definition('TransientKey'));
                  var closeAnchor = jQuery('div.InformWrapper:first').find('a.Close');
                  closeAnchor.attr('callbackurl', dismissCallbackUrl);
                  closeAnchor.click(function () {
                     $.ajax({
                        type: "POST",
                        url: jQuery(this).attr('callbackurl'),
                        data: 'TransientKey='+gdn.definition('TransientKey'),
                        dataType: 'json',
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                           gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
                        },
                        success: function(json) {
                           gdn.inform(json);
                        }
                     });
                  });
               }
            }
         }
      } catch (e) {
      }
   }
   informMessages.show();
   return true;
}

// Send an informMessage to the screen (same arguments as controller.InformMessage).
gdn.informMessage = function(message, options) {
   if (!options)
      options = new Array();

   if (typeof(options) == 'string') {
      var css = options;
      options = new Array();
      options['CssClass'] = css;
   }
   options['Message'] = message;
   if (!options['CssClass'])
      options['CssClass'] = 'Dismissable AutoDismiss';

   gdn.inform({'InformMessages' : new Array(options)});
}

// Inform an error returned from an ajax call.
gdn.informError = function(xhr, silentAbort) {
   if (xhr == undefined || xhr == null)
      return;

   if (typeof(xhr) == 'string')
      xhr = {responseText: xhr, code: 500};

   var message = xhr.responseText;
   var code = xhr.status;

   if (message == undefined || message == null || message == '') {
      switch (xhr.statusText) {
         case 'error':
            if (silentAbort) 
               return;
            message = 'There was an error performing your request. Please try again.';
            break;
         case 'timeout':
            message = 'Your request timed out. Please try again.';
            break;
         case 'abort':
            return;
      }
   }

   try {
      var data = $.parseJSON(message);
      if (typeof(data.Exception) == 'string')
         message = data.Exception;
   } catch(e) {
   }

   if (message == '')
      message = 'There was an error performing your request. Please try again.';

   gdn.informMessage('<span class="InformSprite Lightbulb Error'+code+'"></span>'+message, 'HasSprite Dismissable AutoDismiss');
}

// Ping for new notifications on pageload, and subsequently every 1 minute.
gdn.notificationsPinging = 0;
gdn.pingCount = 0;

gdn.pingForNotifications = function() {
   if (gdn.notificationsPinging > 0 || !gdn.focused)
      return;
   gdn.notificationsPinging++;

   jQuery.ajax({
      type: "POST",
      url: gdn.url('dashboard/notifications/inform'),
      data: {'TransientKey': gdn.definition('TransientKey'), 'Path': gdn.definition('Path'), 'DeliveryMethod': 'JSON', 'Count': gdn.pingCount++},
      dataType: 'json',
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         console.log(XMLHttpRequest.responseText);
      },
      success: function(json) {
         gdn.inform(json);
      },
      complete: function() {
         gdn.notificationsPinging--;
      }
   });
}

gdn.Template = {

   Driver: 'Mustache',
   Templates: {},
   Background: false,

   /**
    * Register a group of templates
    * 
    * @param array Template An array of Template objects suitable for 
    * Template::RegisterTemplate().
    */
   Register: function(Templates) {
      jQuery.each(Templates, function(i, Template){
         gdn.Template.RegisterTemplate(Template)
      });
   },

   /**
    * Register a Template object
    * 
    * Template is a JSON object with the following keys:
    * 
    *  Name       // Name of the template as it is referred to on the serverside
    *  URL        // String Mustache Template contents
    *  Type       // Type of template
    *  Contents   // Optional. Contents of the template.
    *  
    * @param JSON Template Template object
    */
   RegisterTemplate: function(Template) {
      gdn.Template.Templates[Template.Name] = Template;
      gdn.Template.Templates[Template.Name].Downloading = false;

      // Background download templates
      if (gdn.Template.Background && Template.Type == 'defer')
         gdn.Template.Download(Template.Name);
   },

   /**
    * Render the supplied Template and View
    * 
    * Renders the Template with View as the data, then returns the resulting
    * string.
    * 
    * @param string TemplateName Name of the template to render
    * @param JSON View View object for Mustache
    * @param array Partials A list of partial templates to include
    */
   Render: function(TemplateName, View, Partials) {
      var TemplateSrc = gdn.Template.GetTemplateSrc(TemplateName);
      if (TemplateSrc) {
         Partials = gdn.Template.GetPartials(Partials);
         return Mustache.render(TemplateSrc, View, Partials);
      } else {
         return '';
      }
   },

   /**
    * Render Template+View and replace Element with result
    * 
    * Performs a normal Template Render with the supplied Template and View, then
    * replaces the supplied Element with the resulting string.
    * 
    * @param string Template Name of the template to render
    * @param JSON View View object for Mustache
    * @param DOMObject Element DOM object to replace
    * @param array Partials A list of partial templates to include
    */
   RenderInPlace: function(TemplateName, View, Element, Partials) {
      var Render = gdn.Template.Render(TemplateName, View, Partials);
      var Replace = jQuery(Render);
      jQuery(Element).replaceWith(Replace);
      return Replace;
   },

   /**
    * Update the given template
    *
    * @param string TemplateName
    * @param string Field
    * @param mixed Value
    */
   SetTemplateField: function(TemplateName, Field, Value) {
      if (!gdn.Template.Templates.hasOwnProperty(TemplateName)) return false;
      gdn.Template.Templates[TemplateName][Field] = Value;
   },

   /**
    * Get a template object
    *
    * @param string TemplateName
    */
   GetTemplate: function(TemplateName) {
      if (!gdn.Template.Templates.hasOwnProperty(TemplateName)) return false;
      return gdn.Template.Templates[TemplateName];
   },

   /**
    * Get the string contents of the given template name
    * 
    * @param string Template Name of the template to retrieve
    */
   GetTemplateSrc: function(TemplateName) {
      var Template = gdn.Template.GetTemplate(TemplateName);
      if (!Template) return false;

      if (!Template.hasOwnProperty('Contents')) {
         gdn.Template.Download(TemplateName, 'sync');
         Template = gdn.Template.GetTemplate(TemplateName);
      }

      return Template.Contents;

   },

   GetPartials: function(Partials) {
      var PartialList = {};
      if (Partials instanceof Array) {
         jQuery.each(Partials, function(i, PartialName){
            var PartialTemplate = gdn.Template.GetTemplateSrc(PartialName);
            if (!PartialTemplate) return;

            PartialList[PartialName] = PartialTemplate;
         });
      }
      return PartialList;
   },

   Download: function(TemplateName, Mode) {
      var Template = gdn.Template.GetTemplate(TemplateName);

      // We're downloading, don't queue another download
      if (Template.Downloading) return null;
      gdn.Template.SetTemplateField(TemplateName, 'Downloading', true);

      var Async = false;
      switch (Mode) {
         case 'sync':
            Async = false;
            break;

         case 'async':
         default:
            Async = true;
            break;
      }
      var TemplateURL = Template.URL;
      jQuery.ajax({
         url: TemplateURL,
         async: Async,
         dataType: 'html',
         success: function(data, status, xhr) {

            Template.Contents = data;
            gdn.Template.TemplateLoaded(Template)

         },
         error: function(xhr, status, error) {}
      });
   },

   TemplateLoaded: function(Template) {
      // Store template contents
      gdn.Template.SetTemplateField(Template.Name, 'Contents', Template.Contents);
      gdn.Template.SetTemplateField(Template.Name, 'Downloading', false);
   }

}

String.prototype.addCommas = function() {
   nStr = this;
   x = nStr.split('.');
   x1 = x[0];
   x2 = x.length > 1 ? '.' + x[1] : '';
   var rgx = /(\d+)(\d{3})/;
   while (rgx.test(x1)) {
      x1 = x1.replace(rgx, '$1' + ',' + '$2');
   }
   return x1 + x2;
}

Array.prototype.sum = function(){
   for(var i=0,sum=0;i<this.length;sum+=this[i++]);
   return sum;
}

Array.prototype.max = function(){
   return Math.max.apply({},this)
}

Array.prototype.min = function(){
   return Math.min.apply({},this)
}

if (typeof String.prototype.trim !== 'function') {
   String.prototype.trim = function() {
      return this.replace(/^\s+|\s+$/g, ''); 
   }
}


/**
 * Document Ready Event
 * 
 */
jQuery(document).ready(function($) {
   
   var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
   
   var uTF8Encode = function(string) {
      string = string.replace(/\x0d\x0a/g, "\x0a");
      var output = "";
      for (var n = 0; n < string.length; n++) {
         var c = string.charCodeAt(n);
         if (c < 128) {
            output += String.fromCharCode(c);
         } else if ((c > 127) && (c < 2048)) {
            output += String.fromCharCode((c >> 6) | 192);
            output += String.fromCharCode((c & 63) | 128);
         } else {
            output += String.fromCharCode((c >> 12) | 224);
            output += String.fromCharCode(((c >> 6) & 63) | 128);
            output += String.fromCharCode((c & 63) | 128);
         }
      }
      return output;
   };
   
   var uTF8Decode = function(input) {
      var string = "";
      var i = 0;
      var c = c1 = c2 = 0;
      while ( i < input.length ) {
         c = input.charCodeAt(i);
         if (c < 128) {
            string += String.fromCharCode(c);
            i++;
         } else if ((c > 191) && (c < 224)) {
            c2 = input.charCodeAt(i+1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
         } else {
            c2 = input.charCodeAt(i+1);
            c3 = input.charCodeAt(i+2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
         }
      }
      return string;
   }
   
   $.extend({
      base64Encode: function(input) {
         var output = "";
         var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
         var i = 0;
         input = uTF8Encode(input);
         while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);
            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;
            if (isNaN(chr2)) {
               enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
               enc4 = 64;
            }
            output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
         }
         return output;
      },
      base64Decode: function(input) {
         var output = "";
         var chr1, chr2, chr3;
         var enc1, enc2, enc3, enc4;
         var i = 0;
         input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
         while (i < input.length) {
            enc1 = keyString.indexOf(input.charAt(i++));
            enc2 = keyString.indexOf(input.charAt(i++));
            enc3 = keyString.indexOf(input.charAt(i++));
            enc4 = keyString.indexOf(input.charAt(i++));
            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;
            output = output + String.fromCharCode(chr1);
            if (enc3 != 64) {
               output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
               output = output + String.fromCharCode(chr3);
            }
         }
         output = uTF8Decode(output);
         return output;
      }
   });
   
   $.postParseJson = function(json) {
      if (json.Data) json.Data = $.base64Decode(json.Data);
      return json;
   }
   
   // Make sure that the commentbox & aboutbox do not allow more than 1000 characters
   $.fn.setMaxChars = function(iMaxChars) {
      $(this).bind('keyup', function() {
         var txt = $(this).val();
         if (txt.length > iMaxChars)
            $(this).val(txt.substr(0, iMaxChars));
      });
   }

   $.fn.popin = function(options) {
      var settings = $.extend({}, options);
      
      this.each(function(i, elem) {
         var url = $(elem).attr('rel');
         var $elem = $(elem);
         $.ajax({
            url: gdn.url(url),
            data: {DeliveryType: 'VIEW'},
            success: function(data) {
               $elem.html(data);
            },
            complete: function() {
               $elem.removeClass('Progress TinyProgress InProgress');
               if (settings.complete != undefined) {
                  settings.complete($elem);
               }
            }
         });
     });
   };
   
   jQuery(window).blur(function() {
      gdn.focused = false;
   });
   jQuery(window).focus(function(){
      gdn.focused = true;
   });
   
   if ($.browser.msie) {
      $('body').addClass('MSIE');
   }
   
   var d = new Date()
   var hourOffset = -Math.round(d.getTimezoneOffset() / 60);

   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=HourOffset]').livequery(function() {
      $(this).val(hourOffset);
   });
   
   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetHourOffset]').livequery(function() {
      if (hourOffset != $(this).val()) {
         $.post(
            gdn.url('/utility/sethouroffset.json'),
            { HourOffset: hourOffset, TransientKey: gdn.definition('TransientKey') }
         );
      }
   });
   
   var d = new Date();
   var clientDate = d.getFullYear()+'-'+(d.getMonth() + 1)+'-'+d.getDate()+' '+d.getHours()+':'+d.getMinutes();

   // Set the ClientHour if there is an input looking for it.
   $('input:hidden[name$=ClientHour]').livequery(function() {
      $(this).val(clientDate);
   });
   
   // Add "checked" class to item rows if checkboxes are checked within.
   checkItems = function() {
      var container = $(this).parents('.Item');
      if ($(this).attr('checked') == 'checked')
         $(container).addClass('Checked');
      else
         $(container).removeClass('Checked');
   }
   $('.Item :checkbox').each(checkItems);
   $('.Item :checkbox').change(checkItems);

   // Ajax/Save the ClientHour if it is different from the value in the db.
   $('input:hidden[id$=SetClientHour]').livequery(function() {
      if (d.getHours() != $(this).val()) {
         $.get(
            gdn.url('/utility/setclienthour'),
            {'ClientDate': clientDate, 'TransientKey': gdn.definition('TransientKey'), 'DeliveryType': 'BOOL'}
         );
      }
   });
   
   // Hide/Reveal the "forgot your password" form if the ForgotPassword button is clicked.
   $(document).delegate('a.ForgotPassword', 'click', function() {
      // Make sure we have both forms
      if ($('#Form_User_SignIn').length) {
      $('.Methods').toggle();
      $('#Form_User_Password').toggle();
      $('#Form_User_SignIn').toggle();
      return false;
      }
   });
   
   // Reveal youtube player when preview clicked.
   function Youtube(Container) {
      var $preview = Container.find('.VideoPreview');
      var $player = Container.find('.VideoPlayer');
      var width = $preview.width(), height = $preview.height(), videoid = Container.attr('id').replace('youtube-', '');

      $preview.hide();
      $player.html('<object width="'+width+'" height="'+height+'">'
         + '<param name="movie" value="http://www.youtube.com/v/'+videoid+'&amp;hl=en_US&amp;fs=1&amp;autoplay=1"></param>'
         + '<param name="allowFullScreen" value="true"></param>'
         + '<param name="allowscriptaccess" value="always"></param>'
         + '<embed src="http://www.youtube.com/v/'+videoid+'&amp;hl=en_US&amp;fs=1&amp;autoplay=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'+width+'" height="'+height+'"></embed>'
         + '</object>');
      $player.show();
      
      return false;
   }
   $(document).delegate('.Video.YouTube .VideoPreview', 'click', function(e) {
      var $target = $(e.target);
      var $container = $target.closest('.Video.YouTube');
      return Youtube($container);
   });
   
   if ($.fn.autogrow) {
      $('textarea.Autogrow').livequery(function() {
         $(this).autogrow();
      });
   }
   
   // password strength check
   gdn.password = function(password, username) {
      // calculate entropy
      var alphabet = 0;
      if ( password.match(/[0-9]/) )
         alphabet += 10;
      if ( password.match(/[a-z]/) )
         alphabet += 26;
      if ( password.match(/[A-Z]/) )
         alphabet += 26;
      if ( password.match(/[^a-zA-Z0-9]/) )
         alphabet += 31;
      var natLog = Math.log(Math.pow(alphabet, password.length));
      var entropy = natLog / Math.LN2;
      
      var response = {
         pass: false,
         symbols: alphabet,
         entropy: entropy,
         score: 0
      };
      
      // password1 == username
      if (username) {
         if (password.toLowerCase() == username.toLowerCase()) {
            response.reason = 'similar';
            return response;
         }
      }
      
      // divide into entropy buckets
      var entropyBuckets = [10,26,36,41,52,57,83,93];
      var entropyBucket = 1;
      for (var i=0;i<entropyBuckets.length;i++) {
         if (entropy >= entropyBuckets[i]) {
            entropyBucket = i+1;
         }
      }
      entropyBucket = Math.floor(parseFloat(entropyBucket) / parseFloat(2));
      response.entropyBucket = entropyBucket;
      
      // reject on length
      var length = password.length;
      response.length = length;
      var requiredLength = gdn.definition('MinPassLength', 8);
      var requiredScore = gdn.definition('MinPassScore', 2);
      response.required = requiredLength;
      if (length < requiredLength) {
         response.reason = 'short';
         return response;
      }
      
      // divide into length buckets
      var lengthBuckets = [5,7,11,15];
      var lengthBucket = 1;
      for (var i=0; i < lengthBuckets.length; i++) {
         if (length >= lengthBuckets[i]) {
            lengthBucket = i+1;
         }
      }
      
      // apply length modifications
      var zeroBucket = Math.ceil(lengthBuckets.length / 2);
      var bucketMod = lengthBucket - zeroBucket;
      var finalBucket = entropyBucket + bucketMod;
      
      // normalize
      if (finalBucket < 1) finalBucket = 1;
      if (finalBucket > 5) finalBucket = 5;
      
      response.score = finalBucket;
      if (finalBucket >= requiredScore)
         response.pass = true;
      return response;
   }

   // Go to notifications if clicking on a user's notification count
   $('li.UserNotifications a span').click(function() {
      document.location = gdn.url('/profile/notifications');
      return false;
   });
   
   // This turns any anchor with the "Popup" class into an in-page pop-up (the
   // view of the requested in-garden link will be displayed in a popup on the
   // current screen).
   if ($.fn.popup) {
      
      jQuery('a.Popup').livequery(function(){
         $(this).popup();
      });
      
      jQuery('a.PopConfirm').livequery(function(){
         $(this).popup({'confirm' : true, 'followConfirm' : true});
      });
   }

   $(document).delegate(".PopupWindow", 'click', function() {
      var $this = $(this);
      
      if ($this.hasClass('NoMSIE') && $.browser.misie) {
         return;
      }

      var width = $this.attr('popupWidth');width = width ? width : 960;
      var height = $this.attr('popupHeight');height = height ? height : 600;
      var left = (screen.width - width) / 2;
      var top = (screen.height - height) / 2;

      var id = $this.attr('id');
      var href = $this.attr('href');
      if ($this.attr('popupHref'))
         href = $this.attr('popupHref');
      else
         href += gdn.querySep(href)+'display=popup';

      var win = window.open(href, 'Window_' + id, "left="+left+",top="+top+",width="+width+",height="+height+",status=0,scrollbars=0");
      if (win)
         win.focus();
      return false;
   });
   
   // This turns any anchor with the "Popdown" class into an in-page pop-up, but
   // it does not hijack forms in the popup.
   if ($.fn.popup)
      $('a.Popdown').popup({hijackForms: false});
   
   // This turns SignInPopup anchors into in-page popups
   if ($.fn.popup)
      $('a.SignInPopup').popup({containerCssClass:'SignInPopup'});
   
   if ($.fn.popup) {
      $(document).delegate('.PopupClose', 'click', function(event){
         var Popup = $(event.target).parents('.Popup');
         if (Popup.length) {
            var PopupID = Popup.prop('id');
            $.popup.close({popupId: PopupID});
         }
      });
   }

   // Make sure that message dismissalls are ajax'd
   $(document).delegate('a.Dismiss', 'click', function() {
      var anchor = this;
      var container = $(anchor).parent();
      var transientKey = gdn.definition('TransientKey');
      var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;
      $.post($(anchor).attr('href'), data, function(response) {
         if (response == 'TRUE')
            $(container).fadeOut('fast',function() {
               $(this).remove();
            });
      });
      return false;
   });

   // This turns any form into a "post-in-place" form so it is ajaxed to save
   // without a refresh. The form must be within an element with the "AjaxForm"
   // class.
   if ($.fn.handleAjaxForm) {
      $('.AjaxForm').handleAjaxForm();
   }
   
   // Make the highlight effect themable.
   if ($.effects && $.effects.highlight) {
      $.effects.highlight0 = $.effects.highlight;
      
      $.effects.highlight = function(opts) {
         var color = $('#HighlightColor').css('backgroundColor');
         if (color)
            opts.options.color = color;
         return $.effects.highlight0.call(this, opts);
      };
   }

   // Handle ToggleMenu toggling and set up default state
   $('[class^="Toggle-"]').hide(); // hide all toggle containers
   $('.ToggleMenu a').click(function() {
      // Make all toggle buttons and toggle containers inactive
      $(this).parents('.ToggleMenu').find('li').removeClass('Active'); 
      $('[class^="Toggle-"]').hide();
      var item = $(this).parents('li'); // Identify the clicked container
      // The selector of the container that should be revealed.
      var containerSelector = '.Toggle-' + item.attr('class');
      containerSelector = containerSelector.replace(/Handle-/gi, ''); 
      // Reveal the container & make the button active
      item.addClass('Active'); // Make the clicked form button active
      $(containerSelector).show();
      return false;
   });
   $('.ToggleMenu .Active a').click(); // reveal the currently active item.
   
   // Show hoverhelp on hover
   $('.HoverHelp').hover(
      function() {
         $(this).find('.Help').show();
      },
      function() {
         $(this).find('.Help').hide();
      }
   );

   // If a page loads with a hidden redirect url, go there after a few moments.
   var RedirectUrl = gdn.definition('RedirectUrl', '');
   var CheckPopup = gdn.definition('CheckPopup', '');
   if (RedirectUrl != '') {
      if (CheckPopup && window.opener) {
         window.opener.location.replace(RedirectUrl);
         window.close();
      } else {
         document.location.replace(RedirectUrl);
      }
   }

   // Make tables sortable if the tableDnD plugin is present.
   if ($.tableDnD) {
      $("table.Sortable").tableDnD({onDrop: function(table, row) {
         var tableId = $($.tableDnD.currentTable).attr('id');
         // Add in the transient key for postback authentication
         var transientKey = gdn.definition('TransientKey');
         var data = $.tableDnD.serialize() + '&DeliveryType=BOOL&TableID=' + tableId + '&TransientKey=' + transientKey;
         var webRoot = gdn.definition('WebRoot', '');
         $.post(gdn.combinePaths(webRoot, 'index.php?p=/dashboard/utility/sort/'), data, function(response) {
            if (response == 'TRUE')
               $('#'+tableId+' tbody tr td').effect("highlight", {}, 1000);

         });
      }});
   }

   // Fill in placeholders.
   if (!gdn.elementSupports('input', 'placeholder')) {
      $('input:text,textarea').not('.NoIE').each(function() {
         var $this = $(this);
         var placeholder = $this.attr('placeholder');
         
         if (!$this.val() && placeholder) {
            $this.val(placeholder);
            $this.blur(function() {
               if ($this.val() == '')
                  $this.val(placeholder);
            });
            $this.focus(function() {
               if ($this.val() == placeholder)
                  $this.val('');
            });
            $this.closest('form').bind('submit', function() {
               if ($this.val() == placeholder)
                  $this.val('');
            });
         }
      });
   }
   
   // Attach popin functionality to .Popin class
   $('.Popin').popin();
   
   var hijackClick = function(e) {   
      var $elem = $(this);
      var $parent = $(this).closest('.Item');
      var $flyout = $elem.closest('.ToggleFlyout');
      var href = $elem.attr('href');
      var progressClass = $elem.hasClass('Bookmark') ? 'Bookmarking' : 'InProgress';
      
      if (!href)
         return;
      gdn.disable(this, progressClass);
      e.stopPropagation();
      
      $.ajax({
         type: "POST",
         url: href,
         data: { DeliveryType: 'VIEW', DeliveryMethod: 'JSON', TransientKey: gdn.definition('TransientKey') },
         dataType: 'json',
         complete: function() {
            gdn.enable(this);
            $elem.removeClass(progressClass);
            $elem.attr('href', href);
            
            // If we are in a flyout, close it.
            $flyout.removeClass('Open').find('.Flyout').hide();
         },
         error: function(xhr) {
            gdn.informError(xhr);
         },
         success: function(json) {
            if (json == null) json = {};
            
            var informed = gdn.inform(json);
            gdn.processTargets(json.Targets, $elem, $parent);
            // If there is a redirect url, go to it.
            if (json.RedirectUrl) {
               setTimeout(function() {
                     window.location.replace(json.RedirectUrl);
                  },
                  informed ? 3000 : 0);
            }
         }
      });

      return false;
   };
   $(document).delegate('.Hijack', 'click', hijackClick);
   
   // Activate ToggleFlyout and ButtonGroup menus
   $(document).delegate('.ButtonGroup > .Handle', 'click', function() {
      var buttonGroup = $(this).closest('.ButtonGroup');
      if (buttonGroup.hasClass('Open')) {
         // Close
         $('.ButtonGroup').removeClass('Open');
      } else {
         // Close all other open button groups
         $('.ButtonGroup').removeClass('Open');
         // Open this one
         buttonGroup.addClass('Open');
      }
      return false;
   });
   var lastOpen = null;
   $(document).delegate('.ToggleFlyout', 'click', function(e) {        
        
      var $flyout = $('.Flyout', this);
        var isHandle = false;
        
        if ($(e.target).closest('.Flyout').length == 0) {
           e.stopPropagation();
           isHandle = true;
        } else if ($(e.target).hasClass('Hijack') || $(e.target).closest('a').hasClass('Hijack')) {
           return;
        }
        e.stopPropagation();
      
      // Dynamically fill the flyout.
      var rel = $(this).attr('rel');
      if (rel) {
         $(this).attr('rel', '');
         $flyout.html('<div class="InProgress" style="height: 30px"></div>');
         
         $.ajax({
            url: gdn.url(rel),
            data: {DeliveryType: 'VIEW'},
            success: function(data) {
               $flyout.html(data);
            },
            error: function(xhr) {
               $flyout.html('');
               gdn.informError(xhr, true);
            }
         });
      }
      
      if ($flyout.css('display') == 'none') {
         if (lastOpen != null) {
            $('.Flyout', lastOpen).hide();
            $(lastOpen).removeClass('Open').closest('.Item').removeClass('Open');
         }
        
         $(this).addClass('Open').closest('.Item').addClass('Open');
         $flyout.show();
         lastOpen = this;
      } else {
         $flyout.hide();
         $(this).removeClass('Open').closest('.Item').removeClass('Open');
      }
     
        if (isHandle)
           return false;
   });
   
   // Close ToggleFlyout menu even if their links are hijacked
   $(document).delegate('.ToggleFlyout a', 'mouseup', function() {
      if ($(this).hasClass('FlyoutButton'))
         return;
      
      $('.ToggleFlyout').removeClass('Open').closest('.Item').removeClass('Open');
      $('.Flyout').hide();
   });

   $(document).delegate('#Body', 'click', function() {
      if (lastOpen) {
         $('.Flyout', lastOpen).hide();
         $(lastOpen).removeClass('Open').closest('.Item').removeClass('Open');
      }
      $('.ButtonGroup').removeClass('Open');
   });
   
   // Add a spinner onclick of buttons with this class
   $(document).delegate('input.SpinOnClick', 'click', function() {
      $(this).before('<span class="AfterButtonLoading">&#160;</span>').removeClass('SpinOnClick');
   });
   
   // Confirmation for item removals
   $('a.RemoveItem').click(function() {
      if (!confirm('Are you sure you would like to remove this item?')) {
         return false;
      }
   });

   if (window.location.hash == '') {
      // Jump to the hash if desired.
      if (gdn.definition('LocationHash', 0) != 0) {
         window.location.hash = gdn.definition('LocationHash');
      }
      if (gdn.definition('ScrollTo', 0) != 0) {
         var scrollTo = $(gdn.definition('ScrollTo'));
         if (scrollTo.length > 0) {
            $('html').animate({
               scrollTop: scrollTo.offset().top - 10
            });
         }
      }
   }
   
   // Ping back to the deployment server to track views, and trigger
   // conditional stats tasks
   var AnalyticsTask = gdn.definition('AnalyticsTask', false);
   if (AnalyticsTask == 'tick')
      gdn.stats();
   
   // If a dismissable InformMessage close button is clicked, hide it.
   $(document).delegate('div.InformWrapper.Dismissable a.Close', 'click', function() {
      $(this).parents('div.InformWrapper').fadeOut('fast', function() {
         $(this).remove();
      });
   });
   
   // Handle autodismissals
   $('div.InformWrapper.AutoDismiss:first').livequery(function() {
      gdn.setAutoDismiss();
   });
   
   // Prevent autodismiss if hovering any inform messages
   $(document).delegate('div.InformWrapper', 'mouseover mouseout', function(e) {
      if (e.type == 'mouseover') {
         var timerId = $('div.InformMessages').attr('autodismisstimerid');
         if (timerId) {
            clearTimeout(timerId);
            $('div.InformMessages').removeAttr('autodismisstimerid');
         }
      } else {
         gdn.setAutoDismiss();
      }
   });
   
   // Pick up the inform message stack and display it on page load
   var informMessageStack = gdn.definition('InformMessageStack', false);
   if (informMessageStack) {
      informMessageStack = {'InformMessages' : eval($.base64Decode(informMessageStack))};
      gdn.inform(informMessageStack);
   }
   
   if (gdn.definition('SignedIn', '0') != '0' && gdn.definition('DoInform', '1') != '0') {
      setTimeout(gdn.pingForNotifications, 3000);
      setInterval(gdn.pingForNotifications, 60000);
   }
   
   // Stash something in the user's session (or unstash the value if it was not provided)
   stash = function(name, value) {
      $.ajax({
         type: "POST",
         url: gdn.url('session/stash'),
         data: {'TransientKey' : gdn.definition('TransientKey'), 'Name' : name, 'Value' : value},
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            gdn.informMessage(XMLHttpRequest.responseText, 'Dismissable AjaxError');
         },
         success: function(json) {
            gdn.inform(json);
            return json.Unstash;
         }
      });
      
      return '';
   }
   
   // When a stash anchor is clicked, look for inputs with values to stash
   $('a.Stash').click(function() {
      // Embedded comments
      var comment = $('#Form_Comment textarea').val(),
         placeholder = $('#Form_Comment textarea').attr('placeholder'),
         vanilla_identifier = gdn.definition('vanilla_identifier');
      if (vanilla_identifier && comment != '' && comment != placeholder)
         stash('CommentForForeignID_' + vanilla_identifier, comment);
   });
   
});


/**
 * Window Load Event
 * 
 */
jQuery(window).load(function() {

   // Shrink large images to fit into message space, and pop into new window when clicked.
   // This needs to happen in onload because otherwise the image sizes are not yet known.
   var props = ['Width', 'Height'], prop;

   while (prop = props.pop()) {
      (function (natural, prop) {
         jQuery.fn[natural] = (natural in new Image()) ? 
         function () {
            return this[0][natural];
         } : 
         function () {
            var 
               node = this[0],
               img,
               value;

            if (node.tagName.toLowerCase() === 'img') {
               img = new Image();
               img.src = node.src,
               value = img[prop];
            }
            return value;
         };
      }('natural' + prop, prop.toLowerCase()));
   }

   jQuery('div.Message img').each(function(i,img) {
      var img = jQuery(img);
      var container = img.closest('div.Message');
      if (img.naturalWidth() > container.width() && container.width() > 0) {
         img.after('<div class="ImageResized">' + gdn.definition('ImageResized', 'This image has been resized to fit in the page. Click to enlarge.') + '</div>');
         img.wrap('<a href="'+$(img).attr('src')+'"></a>');
      }
   });
   
   // Let the world know we're done here
   jQuery(window).trigger('ImagesResized');
});