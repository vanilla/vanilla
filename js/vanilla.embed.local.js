(function(window, $, Vanilla) {
   // Check to see if we are even embedded.
   if (window.top == window.self)
      return;
   
   window.document.domain = window.document.domain;
   
   // Call a remote function in the parent.
   Vanilla.parent.callRemote = function(func, args, success, failure) {
      window.parent.callRemote(func, args, success, failure);
   };
   
   Vanilla.parent.signout = function() { $.post('/entry/signout.json'); };
   
   Vanilla.urlType = function(url) {
      // Test for an internal link with no domain.
      var regex = /^(https?:)?\/\//i;
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
      
      regex = new RegExp("//[\\w-]+\\." + domain + "($|[/?#])");
      if (regex.test(url))
         return "subdomain";
      
      return "external";
   };
   
   var currentHeight = null;
   var setHeight = function() {
      // Set the height of the iframe based on vanilla.
      var height = document.body.offsetHeight || document.body.scrollHeight;
      
      if (height != currentHeight) {
         currentHeight = height;
         Vanilla.parent.callRemote('height', height);
      }
   }
   
   $(window).load(function() {
      setHeight();
      Vanilla.parent.callRemote('notifyLocation', window.location.href);
      
      setInterval(setHeight, 300);
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
      }
   });
})(window, jQuery, Vanilla);

jQuery(document).ready(function($) {
   if (window.top == window.self)
      return;
   
   $('body').addClass('Embedded');
});