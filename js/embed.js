if (window.vanilla == undefined)
   window.vanilla = {};

window.vanilla.initialize = function (host) {

   var scripts = document.getElementsByTagName('script'),
      id = Math.floor((Math.random()) * 100000).toString(),
      embedUrl = window.location.href.split('#')[0],
      jsPath = '/js/embed.js',
      currentPath = window.location.hash.substr(1),
      disablePath = (window != top);

   var getGlobalConfigVariable = function(name, defaultValue) {
      if (typeof (window['vanilla_'+name]) != 'undefined') {
		return window['vanilla_'+name];
      }
      return defaultValue;
   };

   if (!currentPath || disablePath)
      currentPath = "/";

   if (currentPath.substr(0, 1) != '/')
      currentPath = '/' + currentPath;

   /*
   if (window.gadgets)
      embedUrl = '';
   */

   var host_base_url;

   if (typeof(host) == 'undefined') {
      host = '';
      host_base_url = '';
      for (i = 0; i < scripts.length; i++) {
         if (scripts[i].src.indexOf(jsPath) > 0) {
            host = scripts[i].src;
            host = host.replace('http://', '').replace('https://', '');
            host = host.substr(0, host.indexOf(jsPath));
            host += '/index.php?p=';

            host_base_url = scripts[i].src;
            host_base_url = host_base_url.substr(0, host_base_url.indexOf(jsPath));
            if (host_base_url.substring(host_base_url.length-1) != '/')
               host_base_url += '/';

         }
      }
   }

   // Check hash.
   /*
   var checkHash = function() {
      var path = window.location.hash.substr(1);
      if (path != currentPath) {
         currentPath = path;

         var parts = path.split (':');
         var id = parts.shift ();
         path = parts.join (':');

         var frame = VanillaFrames.get (id);
         if (frame) {
            frame.navigate (path);
         }
      }
   };

   if (!window.gadgets) {
      if (!disablePath) {
         if ("onhashchange" in window) {
            if (window.addEventListener)
               window.addEventListener("hashchange", checkHash, false);
            else
               window.attachEvent("onhashchange", checkHash);
         } else {
            setInterval(checkHash, 300);
         }
      }
   }
   */

   // Strip param out of str if it exists
   var stripParam = function(str, param) {
      var pIndex = str.indexOf(param);
      if (pIndex > -1) {
         var pStr = str.substr(pIndex);
         var tIndex = pStr.indexOf('&');
         var trail = tIndex > -1 ? pStr.substr(tIndex+1) : '';
         var pre = currentPath.substr(pIndex-1, 1);
         if (pre == '&' || pre == '?')
            pIndex--;

         return str.substr(0, pIndex) + (trail.length > 0 ? pre : '') + trail;
      }
      return str;
   };

   var processMessage = function(message) {

      var id = message.shift ();
      var frame = VanillaFrames.get (id);

      if (!frame) {
         console.log ('Frame not found');
         return;
      }

      var iframe = frame.iframe;

      if (message[0] == 'height') {
         frame.setHeight(message[1]);

         if (message[1] > 0) {
            iframe.style.visibility = "visible";
         }

      } else if (message[0] == 'location') {
         frame.updatePath (message[1]);
      } else if (message[0] == 'unload') {
         if (window.attachEvent || frame.getScrollPosition () < 0)
            iframe.scrollIntoView(true);

         iframe.style.visibility = "hidden";

      } else if (message[0] == 'scrolltop') {
         window.scrollTo(0, iframe.offsetTop);
      } else if (message[0] == 'scrollto') {
         window.scrollTo(0, iframe.offsetTop - 40 + (message[1] * 1));
      } else if (message[0] == 'unembed') {
         document.location = 'http://' + host + window.location.hash.substr(1);
      }
   };

   function registerMessageListener () {
      if (typeof (window.postMessage) != 'undefined') {
         var onMessage = function(e) {
            var message = e.data.split(':');
            processMessage(message);
         };

         if (window.addEventListener)
            window.addEventListener("message", onMessage, false);
         else
            window.attachEvent("onmessage", onMessage);

      }
      else {
         var messageId = null;
         setInterval(function() {
            try {
               var vid = 'vanilla' + id;
               var hash = window.frames[vid].frames['messageFrame'].location.hash;
               hash = hash.substr(6);
            } catch(e) {
               return;
            }

            var message = hash.split(':');
            var newMessageId = message[0];
            if (newMessageId == messageId)
               return;

            messageId = newMessageId;
            message.splice(0, 1);
            self.processMessage(message);
         }, 200);
      }
   }

   registerMessageListener ();

   var VanillaFrames = {

      'map' : {},

      'add' : function (frame) {
         this.map[frame.id] = frame;
      },

      'get' : function (id) {
         return this.map[id];
      },

      'remove' : function () {

      }

   };

   // A single iframe with the forum loaded inside.
   var VanillaFrame = function (container, settings)
   {

      this.container = container;
      this.id = Math.floor (Math.random () * 100000);
      this.settings = settings || {};

      this.register ();
      this.initialize ();
      this.load ();
   };

   VanillaFrame.prototype.register = function () {
      VanillaFrames.add (this);
   };

   VanillaFrame.prototype.initialize = function () {
      var vanillaIframe = document.createElement('iframe');
      this.iframe = vanillaIframe;

      vanillaIframe.id = "vanilla"+id;
      vanillaIframe.name = "vanilla"+id;
      vanillaIframe.src = this.getURL (currentPath);
      vanillaIframe.scrolling = "no";
      vanillaIframe.frameBorder = "0";
      vanillaIframe.allowTransparency = true;
      vanillaIframe.border = "0";
      vanillaIframe.width = "100%";
      vanillaIframe.style.width = "100%";
      vanillaIframe.style.border = "0";
      vanillaIframe.style.display = "block"; // must be block

      if (typeof (window.postMessage) != 'undefined') {
         vanillaIframe.height = "0";
         vanillaIframe.style.height = "0";
      } else {
         vanillaIframe.height = "300";
         vanillaIframe.style.height = "300px";
      }
   };

   VanillaFrame.prototype.load = function () {

      var vanillaIframe = this.iframe;
      var container = this.container;

      var img = document.createElement('div');
      img.className = 'vn-loading';
      img.style.textAlign = 'center';
      img.innerHTML = window.vanilla_loadinghtml ? vanilla_loadinghtml : '<img src="https://cd8ba0b44a15c10065fd-24461f391e20b7336331d5789078af53.ssl.cf1.rackcdn.com/images/progress.gif" />';

      var loaded = function() {
         if (img) {
            container.removeChild(img);
            img = null;
         }
         vanillaIframe.style.visibility = "visible";
      };

      if(vanillaIframe.addEventListener) {
         vanillaIframe.addEventListener('load', loaded, true);
      } else if(vanillaIframe.attachEvent) {
         vanillaIframe.attachEvent('onload', loaded);
      } else
         setTimeout(2000, loaded);

      container.appendChild(img);

      // If jQuery is present in the page, include our defer-until-visible script
      if (this.getSetting ('lazy_loading') && typeof jQuery != 'undefined') {
         jQuery.ajax({
            url: host_base_url+'js/library/jquery.appear.js',
            dataType: 'script',
            cache: true,
            success: function() {
               if (jQuery.fn.appear)
                  jQuery('#vanilla-comments').appear(function() {container.appendChild(vanillaIframe);});
               else
                  container.appendChild(vanillaIframe); // fallback
            }});
      } else {
         container.appendChild(vanillaIframe); // fallback: just load it
      }
   };

   VanillaFrame.prototype.getSetting = function (name, defaultValue) {

      if (typeof (this.settings[name]) != 'undefined') {
         return this.settings[name];
      }
      return defaultValue;
   };

   VanillaFrame.prototype.getURL = function (path) {
      var result = '';

      // Check for root path
      if (typeof (path) == 'undefined')
         path = '/';

      var basepath = this.getSetting ('base_path', '');
      if (basepath) {

         if (path.substr (0, basepath.length) === basepath) {
            path = path.substr (basepath.length);
         }
      }

      if (this.getSetting ('embed_type') == 'comments') {

         result = '//' + host + '/discussion/embed/'
         +'&embed=' + this.id
         +'&vanilla_identifier='+encodeURIComponent(this.getSetting ('foreign_id'))
         +'&vanilla_url='+encodeURIComponent(this.getSetting ('foreign_url'));

         if (this.getSetting ('type'))
            result += '&vanilla_type='+encodeURIComponent(this.getSetting ('type'));

         if (this.getSetting ('discussion_id'))
            result += '&vanilla_discussion_id='+encodeURIComponent(this.getSetting ('discussion_id'));

         if (this.getSetting ('category_id'))
            result += '&vanilla_category_id='+encodeURIComponent(this.getSetting ('category_id'));

         if (this.getSetting ('title'))
            result += '&title='+encodeURIComponent(this.getSetting ('title'));
      }

      else {
         result = '//' +host +path
         +'&embed=' + this.id
         +'&remote=' +encodeURIComponent(embedUrl)
         +'&locale=' +encodeURIComponent(this.getSetting ('embed_locale'));
      }

      if (this.getSetting ('sso')) {
         result += '&sso='+encodeURIComponent(this.getSetting ('sso'));
      }

      return result.replace(/\?/g, '&').replace('&', '?'); // Replace the first occurrence of amp with question.
   };

   VanillaFrame.prototype.setHeight = function (height) {
      if (this.getSetting ('height'))
         return;

      this.iframe.style['height'] = height + "px";

      /*
      if (window.gadgets && gadgets.window && gadgets.window.adjustHeight) {
         try {
            gadgets.window.adjustHeight();
         } catch (ex) {
            // Do nothing...
         }
      }
      */
   };

   VanillaFrame.prototype.getScrollPosition = function() {
      var node = this.iframe,
          top = 0,
          topScroll = 0;
      if (node.offsetParent) {
         do {
            top += node.offsetTop;
            topScroll += node.offsetParent ? node.offsetParent.scrollTop : 0;
         } while (node = node.offsetParent);
         return top - topScroll;
      }
      return -1;
   };

   VanillaFrame.prototype.navigate = function (path) {
      alert (path);
   };

   VanillaFrame.prototype.updatePath = function (path) {


      if (path != '') {
         this.iframe.style.visibility = "visible";
      }

      if (disablePath) {
         //currentPath = cmd[1];
      }

      else if (this.getSetting ('update_path', true)) {
         var currentPath = window.location.hash.substr(1);
         if (currentPath != path) {
            currentPath = path;
            // Strip off the values that this script added
            currentPath = currentPath.replace('/index.php?p=', ''); // 1
            currentPath = stripParam(currentPath, 'remote='); // 2
            currentPath = stripParam(currentPath, 'locale='); // 3
            window.location.hash = this.getSetting ('base_path', '') + currentPath;
         }
      }
   };

   // Expose embed method.
   window.vanilla.embed = function (container, options) {
      new VanillaFrame (container, options);
   };

   // Now embed the iframe.
   if (! (getGlobalConfigVariable ('no_embed', false))) {

      var container = document.getElementById('vanilla-comments');
      // Couldn't find the container, so dump it out and try again.
      if (!container) {
         document.write('<div id="vanilla-comments"></div>');
         container = document.getElementById('vanilla-comments');
      }

      /**
       * Globally defined options, to be used in the "first" embedding
       */
      // What type of embed are we performing?
      var embed_type = getGlobalConfigVariable ('embed_type', 'standard');

      // Are we loading a particular discussion based on discussion_id?
      var discussion_id = getGlobalConfigVariable ('discussion_id', 0);

      // Are we loading a particular discussion based on foreign_id?
      var foreign_id = getGlobalConfigVariable ('identifier', '');

      // Force type based on incoming variables
      if (discussion_id != 0 || foreign_id != '')
         embed_type = 'comments';

      // Is there a foreign type defined? Possibly used to render the discussion
      // body a certain way in the forum? Also used to filter down to foreign
      // types so that matching foreign_id's across type don't clash.
      var foreign_type = getGlobalConfigVariable ('type', 'page');

      // If embedding comments, should the newly created discussion be placed in a specific category?
      var category_id = getGlobalConfigVariable ('category_id', '');

      // If embedding comments, this value will be used to reference the foreign content. Defaults to the url of the page this file is included in.
      var foreign_url = getGlobalConfigVariable ('url', document.URL.split('#')[0]);

      // Are we forcing a locale via Multilingual plugin?
      var embed_locale = getGlobalConfigVariable ('embed_locale', '');

      // If path was defined, and we're sitting at app root, use the defined path instead.
	   /*
      if (typeof(vanilla_path) != 'undefined' && path == '/')
         path = vanilla_path;
         */

      var options = {
         'embed_type' : embed_type,
         'discussion_id' : discussion_id,
         'foreign_id' : foreign_id,
         'foreign_type' : foreign_type,
         'category_id' : category_id,
         'foreign_url' : foreign_url,
         'embed_locale' : embed_locale,
         'path' : path,
         'update_path' : true
      };

	  if (typeof (window.vanilla_sso) != 'undefined') {
		  options.sso = window.vanilla_sso;
	  }

      window.vanilla.embed (container, options);
   }

   // Include our embed css into the page
   var vanilla_embed_css = document.createElement('link');
   vanilla_embed_css.rel = 'stylesheet';
   vanilla_embed_css.type = 'text/css';
   vanilla_embed_css.href = host_base_url + (host_base_url.substring(host_base_url.length-1) == '/' ? '' : '/') +'applications/dashboard/design/embed.css';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_embed_css);

   return this;
};

try {
   if (
       window.location.hash.substr(0, 6) != "#poll:"
   ) {
      window.vanilla.initialize();
   }
} catch(e) {
   var error = document.createElement('div');
   error.style.padding = "10px";
   error.style.fontSize = "12px";
   error.style.fontFamily = "lucida grande";
   error.style.background = "#ffffff";
   error.style.color = "#000000";
   error.appendChild(document.createTextNode("Failed to embed Vanilla: " + e));
   (document.getElementById('vanilla-comments')).appendChild(error);
}
