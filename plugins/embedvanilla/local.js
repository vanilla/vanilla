$(function() {
   if (typeof(gdn) == "undefined") {
      gdn = {};
      gdn.definition = function() {
         return '';
      }
   }

   var currentHeight = null,
      minHeight = 600,
      remotePostMessage = null,
      inIframe = top !== self,
      inDashboard = gdn.definition('InDashboard', '') != '',
      embedDashboard = gdn.definition('EmbedDashboard', '') != '',
      remoteUrl = gdn.definition('RemoteUrl', ''),
      forceRemoteUrl = gdn.definition('ForceRemoteUrl', '') != '',
      webroot = gdn.definition('WebRoot'),
      pathroot = gdn.definition('UrlFormat').replace('/{Path}', '').replace('{Path}', '');

   if (inIframe) {
      if ("postMessage" in parent) {
         remotePostMessage = function(message, target) {
            return parent.postMessage(message, target);
         }
         setLocation = function(newLocation) {
            parent.window.frames[0].location.replace(newLocation);
         }
      } else {
         var messages = [];
         messageUrl = function(message) {
            var id = Math.floor(Math.random() * 100000);
            if (remoteUrl.substr(remoteUrl.length - 1) != '/')
               remoteUrl += '/';

            return remoteUrl + "poll.html#poll:" + id + ":" + message;
//            return remoteUrl + "#poll:" + id + ":" + message;
         }

         remotePostMessage = function(message, target) {
            if (message.indexOf(':') >= 0) {
               // Check to replace a similar message.
               var messageType = message.split(':')[0];
               for (var i = 0; i < messages.length; i++) {
                  var messageI = messages[i];
                  if (messageI.length >= messageType.length && messageI.substr(0, messageType.length) == messageType) {
                     messages[i] = message;
//                     console.log('Set message: '+message + ', ('+messageI+')' + messages.length);
                     return;
                  }
               }
            }
//            console.log('Push message: '+message + ', ' + messages.length);
            messages.push(message);
         }

         setLocation = function(newLocation) {
            if (messages.length == 0)
               parent.window.frames[0].location.replace(newLocation);
            else {
               setTimeout(function(){
                  setLocation(newLocation);
               },500);
            }
         }

         var nextMessageTime = new Date();
         setMessage = function() {
            if (messages.length == 0)
               return;

            var messageTime = new Date();
            if (messageTime < nextMessageTime)
               return;
//            console.log('Polling. messageTime: '+messageTime.toString()+', nextMessageTime: '+nextMessageTime);

            messageTime.setSeconds(messageTime.getSeconds() + 2);
            nextMessageTime = messageTime;

            var message = messages.splice(0, 1)[0];
            var url = messageUrl(message);

            document.getElementById('messageFrame').src = url;
         }

         $(function() {
            var body = document.getElementsByTagName("body")[0],
               messageIframe = document.createElement("iframe");

            messageIframe.id = "messageFrame";
            messageIframe.name = "messageFrame";
            messageIframe.src = messageUrl('');
            messageIframe.style.display = "none";
            body.appendChild(messageIframe);
            setMessage();
            setInterval(setMessage, 300);
         });
      }
   }

   // If not embedded and we should be, redirect to the embedded version.
   if (!inIframe && forceRemoteUrl && (remoteUrl != '' || !(inDashboard && !embedDashboard))) {
      var path = document.location.toString().substr(webroot.length);
      var hashIndex = path.indexOf('#');
      if (hashIndex > -1)
         path = path.substr(0, hashIndex);

      document.location = remoteUrl + '#' + path;
   }

   // hijack all anchors to see if they should go to "top" or be within the embed (ie. are they in Vanilla or not?)
   if (inIframe) {
      setHeight = function() {
         var newHeight = document.body.offsetHeight;
         if (newHeight < minHeight)
            newHeight = minHeight;
         if (newHeight != currentHeight) {
            currentHeight = newHeight;

            remotePostMessage('height:'+currentHeight, '*');
         }
      }

      setHeight();
      setInterval(setHeight, 300);

      // Simulate a page unload when popups are opened (so they are scrolled into view).
      $('body').bind('popupReveal', function() {
         remotePostMessage('scrollto:' + $('div.Popup').offset().top, '*');
      });

      $(window).unload(function() { remotePostMessage('unload', '*'); });
   }
   else return; // Ignore the rest if we're not embedded.

   var path = gdn.definition('Path', '~');
   if (path != '~') {
      if (path.length > 0 && path[0] != '/')
         path = '/'+path;
      remotePostMessage('location:' + path, '*');
   } else {
      $(document).on('click', 'a', function() {
         var href = $(this).attr('href'),
            isHttp = href.substr(0, 7) == 'http://' || href.substr(0,8) == 'https://';

         if (isHttp && href.substr(0, webroot.length) != webroot) {
            $(this).attr('target', '_blank');
         } else {
            // Strip the path from the root folder of the app
            var path = isHttp ? href.substr(webroot.length) : href.substr(pathroot.length);
            var hashIndex = path.indexOf('#');
            var hash = '';
            if (hashIndex > -1) {
               hash = path.substr(hashIndex);
               path = path.substr(0, hashIndex);
            }

            if (path != '')
               remotePostMessage('location:' + path, '*');

            // setLocation(pathroot + path + hash);
            // return false;
         }
      });
   }

   // Unembed the dashboard.
   // Note: This must be done AFTER the location is set.
   if (inIframe && inDashboard && !embedDashboard) {
      remotePostMessage('unembed', '*');
   }

});
