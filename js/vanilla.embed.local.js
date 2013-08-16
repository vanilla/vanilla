(function(window, $, Vanilla) {
   // Check to see if we are even embedded.
   if (window.top == window.self)
      return;
   
   // Call a remote function in the parent.
   Vanilla.parent.callRemote = function(func, args, success, failure) {
      window.parent.callRemote(func, args, success, failure);
   };
   
   Vanilla.parent.adjustPopupPosition = function(pos) {
       var height = document.body.offsetHeight || document.body.scrollHeight;
       
       var bottom0 = height - (pos.top + pos.height);
       if (bottom0 < 0)
           bottom0 = 0;
       
       // Move the inform messages.
       $('.InformMessages').animate({ bottom: bottom0 });
       
       // Move the popup.
       $('div.Popup').each(function() { 
           $(this).animate({ top: pos.top + (pos.height - $(this).height()) / 2.2 });
       });
   }
   $(document).on('informMessage popupReveal', function() {
       Vanilla.parent.callRemote('getScrollPosition', [], Vanilla.parent.adjustPopupPosition);
   });
   
   Vanilla.parent.signout = function() { $.post('/entry/signout.json'); };
   
   Vanilla.urlType = function(url) {
      var regex = /^#/;
      if (regex.test(url))
         return 'hash';
      
      // Test for an internal link with no domain.
      regex = /^(https?:)?\/\//i;
      if (!regex.test(url))
         return 'internal';

      // Test for the same domain.
      regex = new RegExp("//" + location.host + "($|[/?#])");
      if (regex.test(url))
         return 'internal';

      // Test for a subdomain.
      var parts = location.host.split(".");
      if (parts.length > 2)
         parts = parts.slice(parts.length - 2);
      var domain = parts.join(".");
      
      regex = new RegExp("//.+\\." + domain + "($|[/?#])");
      if (regex.test(url))
         return "subdomain";
      
      return "external";
   };
   
   var currentHeight = null;
   Vanilla.parent.setHeight = function() {
      // Set the height of the iframe based on vanilla.
      var height = document.body.offsetHeight || document.body.scrollHeight;
      
      if (height != currentHeight) {
         currentHeight = height;
//         console.log('setHeight: ' + height);
         Vanilla.parent.callRemote('height', height);
      }
   }
   
   $(window).load(function() {
      Vanilla.parent.setHeight();
      Vanilla.parent.callRemote('notifyLocation', window.location.href);
      
      setInterval(Vanilla.parent.setHeight, 300);
   });
   
   $(window).unload(function() {
      window.parent.hide();
   });
   
   $(document).on('click', 'a', function (e) {
      var href = $(this).attr('href');
      if (!href)
         return;
      
      switch (Vanilla.urlType(href)) {
         case 'subdomain':
            $(this).attr('target', '_top');
            break;
         case 'external':
            $(this).attr('target', '_blank');
            break;
         case 'internal':
            $(this).attr('target', '');
            break;
      }
   });
   
   $(window).unload(function() { Vanilla.parent.callRemote('scrollTo', 0); });
})(window, jQuery, Vanilla);

jQuery(document).ready(function($) {
   if (window.top == window.self)
      return;
   
   Vanilla.parent.setHeight();
   window.parent.show();
   
   $('body').addClass('Embedded');
});