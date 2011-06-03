if (window.vanilla == undefined)
   window.vanilla = {};

if (window.vanilla.embeds == undefined)
   window.vanilla.embeds = {};

window.vanilla.embed = function(host) {
   var scripts = document.getElementsByTagName('script'),
      id = Math.floor((Math.random()) * 100000).toString(),
      embedUrl = window.location.href.split('#')[0],
      jsPath = '/js/embed.js',
      currentPath = window.location.hash.substr(1),
      disablePath = currentPath && currentPath[0] != "/";
      disablePath |= (window != top);

   var optStr = function(name, defaultValue, definedValue) {
      if (window['vanilla_'+name]) {
         if (definedValue == undefined)
            return window['vanilla_'+name];
         else
            return definedValue.replace('%s', window['vanilla_'+name]);
      }
      return defaultValue;
   }

   if (!currentPath || disablePath)
      currentPath = "/";

   if (window.gadgets)
      embedUrl = '';
      
   if (typeof(host) == 'undefined') {
      host = '';
      for (i = 0; i < scripts.length; i++) {
         if (scripts[i].src.indexOf(jsPath) > 0) {
            host = scripts[i].src;
            host = host.replace('http://', '').replace('https://', '');
            host = host.substr(0, host.indexOf(jsPath));
            host += '/index.php?p=';
         }
      }
   }
      
   window.vanilla.embeds[id] = this;
   if (window.postMessage) {
      onMessage = function(e) {
         var message = e.data.split(':');
         var frame = document.getElementById('vanilla'+id);
         if (frame.contentWindow != e.source)
            return;
         processMessage(message);
      }
      if (window.addEventListener)
         window.addEventListener("message", onMessage, false);
      else
         window.attachEvent("onmessage", onMessage);
   } else {
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
         processMessage(message);
      }, 200);
   }

   checkHash = function() {
      var path = window.location.hash.substr(1);
      if (path != currentPath) {
         currentPath = path;
         window.frames['vanilla'+id].location.replace(vanillaUrl(path));
      }
   }

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

   processMessage = function(message) {
      if (message[0] == 'height') {
         setHeight(message[1]);
      } else if (message[0] == 'location') {
         if (disablePath) {
            //currentPath = cmd[1];
         } else {
            currentPath = window.location.hash.substr(1);
            if (currentPath != message[1]) {
               currentPath = message[1];
               window.location.hash = currentPath; //replace(embedUrl + "#" + currentPath);
            }
         }
      } else if (message[0] == 'unload') {
         if (window.attachEvent || scrollPosition('vanilla'+id) < 0)
            document.getElementById('vanilla'+id).scrollIntoView(true);

      } else if (message[0] == 'scrolltop') {
         window.scrollTo(0, document.getElementById('vanilla'+id).offsetTop);
      } else if (message[0] == 'scrollto') {
         window.scrollTo(0, document.getElementById('vanilla'+id).offsetTop - 40 + (message[1] * 1));
      } else if (message[0] == 'unembed') {
         document.location = 'http://' + host + window.location.hash.substr(1);
      }
   }

   scrollPosition = function(id) {
      var node = document.getElementById(id),
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
   }

   setHeight = function(height) {
      if (optStr('height'))
         return;

      document.getElementById('vanilla'+id).style['height'] = height + "px";
      if (window.gadgets && gadgets.window.adjustHeight)
         gadgets.window.adjustHeight();
   }

   vanillaUrl = function(path) {
      // What type of embed are we performing?
      var embed_type = typeof(vanilla_embed_type) == 'undefined' ? 'standard' : vanilla_embed_type;
      // Are we loading a particular discussion based on discussion_id?
      var discussion_id = typeof(vanilla_discussion_id) == 'undefined' ? 0 : vanilla_discussion_id;
      // Are we loading a particular discussion based on foreign_id?
      var foreign_id = typeof(vanilla_identifier) == 'undefined' ? '' : vanilla_identifier;
      // Is there a foreign type defined? Possibly used to render the discussion
      // body a certain way in the forum? Also used to filter down to foreign
      // types so that matching foreign_id's across type don't clash.
      var foreign_type = typeof(vanilla_type) == 'undefined' ? 'page' : vanilla_type;
      // If embedding comments, should the newly created discussion be placed in a specific category?
      var category_id = typeof(vanilla_category_id) == 'undefined' ? '' : vanilla_category_id;
      // If embedding comments, this value will be used as the newly created discussion title.
      var foreign_name = typeof(vanilla_name) == 'undefined' ? '' : vanilla_name;
      // If embedding comments, this value will be used to reference the foreign content. Defaults to the url of the page this file is included in.
      var foreign_url = typeof(vanilla_url) == 'undefined' ? document.URL.split('#')[0] : vanilla_url;
      // If embedding comments, this value will be used as the first comment body related to the discussion.
      var foreign_body = typeof(vanilla_body) == 'undefined' ? '' : vanilla_body;
      
      // Force type based on incoming variables
      if (discussion_id != '' || foreign_id != '')
         embed_type = 'comments';
         
      if (embed_type == 'comments') {
         return 'http://' + host + '/vanilla/discussion/embed/'
            +'?vanilla_discussion_id='+encodeURIComponent(discussion_id)
            +'&vanilla_identifier='+encodeURIComponent(foreign_id)
            +'&vanilla_type='+encodeURIComponent(foreign_type)
            +'&vanilla_name='+encodeURIComponent(foreign_name)
            +'&vanilla_url='+encodeURIComponent(foreign_url)
            +'&vanilla_body='+encodeURIComponent(foreign_body)
            +'&vanilla_category_id='+encodeURIComponent(category_id);
      } else 
         return 'http://' + host + path + '&remote=' + encodeURIComponent(embedUrl);
   }
   var vanillaIframe = document.createElement('iframe');
   vanillaIframe.id = "vanilla"+id;
   vanillaIframe.name = "vanilla"+id;
   vanillaIframe.src = vanillaUrl(currentPath);
   vanillaIframe.scrolling = "no";
   vanillaIframe.frameborder = "0";
   vanillaIframe.allowtransparency = true;
   vanillaIframe.border = "0";
   vanillaIframe.width = "100%";
   vanillaIframe.height = "1000";
   vanillaIframe.style.width = "100%";
   vanillaIframe.style.height = "1000px";
   vanillaIframe.style.border = "0";
   vanillaIframe.style.display = "block";
   (document.getElementById('vanilla-comments')).appendChild(vanillaIframe);
   
   // Include our embed css into the page
   var vanilla_embed_css = document.createElement('link');
   vanilla_embed_css.rel = 'stylesheet';
   vanilla_embed_css.type = 'text/css';
   vanilla_embed_css.href = vanilla_forum_url + (vanilla_forum_url.substring(vanilla_forum_url.length-1) == '/' ? '' : '/') +'applications/dashboard/design/embed.css';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_embed_css);
   
   return this;
};
try {
   if (window.location.hash.substr(0, 6) != "#poll:")
      window.vanilla.embed();
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