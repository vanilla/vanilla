jQuery(document).ready(function($) {
   if (typeof(gdn) == "undefined") {
      gdn = {};
      gdn.definition = function() {
         return '';
      }
   }
   
   /*
    Embedded pages can have very low height settings. As a result, when an
    absolutely positioned popup appears on the page, iframed content doesn't
    know to increase the page height. So, we need to detect when popups appear
    and increase the page height manually so the container knows to do the same.
   */
   popupHeight = function() {
      var height = ($.popup.getPagePosition().top*1) + ($('.Popup').height()*1);
      setHeight(height); // Set it immediately to prevent content being cut off.
      $('body').css('minHeight', height+'px');
   }
   $('body').bind('popupLoading', popupHeight); // set it when popup loading window appears
   $('body').bind('popupReveal', popupHeight); // reset it when the final popup is revealed
   
      
   var currentHeight = null,
      minHeight = 100,
      remotePostMessage = function(message, target) {},
      remoteUrl = gdn.definition('RemoteUrl', ''),
      inIframe = top !== self,
      inDashboard = gdn.definition('InDashboard') == '1',
      forceEmbedDashboard = gdn.definition('ForceEmbedDashboard') == '1',
      forceEmbedForum = gdn.definition('ForceEmbedForum') == '1',
      pagePath = gdn.definition('Path', ''),
      isEmbeddedComments = pagePath.substring(0, 24) == 'vanilla/discussion/embed',
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
         }
        
         remotePostMessage = function(message, target) {
            if (message.indexOf(':') >= 0) {
               // Check to replace a similar message.
               var messageType = message.split(':')[0];
               for (var i = 0; i < messages.length; i++) {
                  var messageI = messages[i];
                  if (messageI.length >= messageType.length && messageI.substr(0, messageType.length) == messageType) {
                     messages[i] = message;
                     return;
                  }
               }
            }
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
   if (!inIframe && remoteUrl != '') {
      var path = document.location.toString().substr(webroot.length);
      var hashIndex = path.indexOf('#');
      if (hashIndex > -1)
         path = path.substr(0, hashIndex);
      
      if ((inDashboard && forceEmbedDashboard) || (!inDashboard && forceEmbedForum)) {
         document.location = remoteUrl + '#' + path;
      }
   }
   
   // unembed if in the dashboard, in an iframe, and not forcing dashboard embed   
   if (inIframe && inDashboard && !forceEmbedDashboard) {
      remotePostMessage('unembed', '*');
   }

   // hijack all anchors to see if they should go to "top" or be within the embed (ie. are they in Vanilla or not?)
   if (inIframe) {
      setHeight = function(explicitHeight) {
         var newHeight = explicitHeight != undefined ? explicitHeight : document.body.offsetHeight;
         if (newHeight < minHeight)
            newHeight = minHeight;

         if (newHeight != currentHeight) {
            currentHeight = newHeight;               
            remotePostMessage('height:'+currentHeight, '*');
         }
      }
   
      setInterval(setHeight, 300);
    
      // Simulate a page unload when popups are opened (so they are scrolled into view).
      $('body').bind('popupReveal', function() {
         remotePostMessage('scrollto:' + $('div.Popup').offset().top, '*');
      });
      
      $(window).unload(function() { remotePostMessage('unload', '*'); });

      $('a').live('click', function() {
         var href = $(this).attr('href');
         if (!href)
            return;
         
         var isHttp = href.substr(0, 7) == 'http://' || href.substr(0,8) == 'https://',
            noTop = $(this).hasClass('SignOut') || $(this).hasClass('NoTop');
            
         if (isHttp && href.substr(0, webroot.length) != webroot) {
            $(this).attr('target', '_blank');
         } else if (isEmbeddedComments) {
            // If clicking a pager link, just follow it.
            if ($(this).parents('.Pager').length > 0)
               noTop = true;
            
            // Target the top of the page if clicking an anchor in a list of embedded comments
            if (!noTop)
               $(this).attr('target', '_top');

            // Change the post-registration target to the page that is currently embedded.
            if ($(this).parents('.CreateAccount').length > 0) {
               // Examine querystring parameters for a target & replace it with the container page
               $(this).attr('target', '_top');
               var href = $(this).attr('href');
               var targetIndex = href.indexOf('Target=');
               if (targetIndex > 0) {
                  var target = href.substring(targetIndex + 7);
                  var afterTarget = '';
                  if (target.indexOf('&') > 0)
                     afterTarget = target.substring(target.indexOf('&'));
                  
                  $(this).attr('href', href.substring(0, targetIndex + 7)
                     + encodeURIComponent(gdn.definition('vanilla_url', ''))
                     + afterTarget);
               }
            }            
            return;
         }
      });
   }
   
   // DO NOT set the parent location if this is a page of embedded comments!!
   var path = gdn.definition('Path', '~');
   if (path != '~' && !isEmbeddedComments) {
      if (path.length > 0 && path[0] != '/')
         path = '/'+path;
      remotePostMessage('location:' + path, '*');   
   }
});
