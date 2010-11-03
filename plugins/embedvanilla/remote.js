if (window.vanilla == undefined)
   window.vanilla = {};

if (window.vanilla.embeds == undefined)
   window.vanilla.embeds = {};

window.vanilla.embed = function(host) {
   var scripts = document.getElementsByTagName('script'),
      id = Math.floor((Math.random()) * 100000).toString(),
      embedUrl = window.location.href.split('#')[0],
      jsPath = '/plugins/embedvanilla/remote.js',
      currentPath = window.location.hash.substr(1),
      disablePath = currentPath && currentPath[0] != "/";
      disablePath |= (window != top);

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
            var hash = window.frames[vid].frames['messageFrame'].location.hash.substr(6);
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
      }, 300);
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
            currentPath = cmd[1];
         } else {
            currentPath = window.location.hash.substr(1);
            if (currentPath != message[1]) {
               currentPath = message[1];
               location.href = embedUrl + "#" + currentPath;
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
      document.getElementById('vanilla'+id).style['height'] = height + "px";
      if (window.gadgets)
         gadgets.window.adjustHeight();
   }

   vanillaUrl = function(path) {
      return 'http://' + host + path + '&remote=' + encodeURIComponent(embedUrl);
   }

   document.write('<iframe id="vanilla'+id+'" name="vanilla'+id+'" src="'+vanillaUrl(currentPath)+'" scrolling="no" frameborder="0" border="0" width="100%" height="1000" style="width: 100%; height: 1000px; border: 0; display: block;"></iframe>');
   return this;
};
try {
   if (window.location.hash.substr(0, 6) != "#poll:")
      window.vanilla.embed();
} catch(e) {
   document.write("<div style=\"padding: 10px; font-size: 12px; font-family: 'lucida grande'; background: #fff; color:#000;\">Failed to embed Vanilla: " + e + "</div>");
}