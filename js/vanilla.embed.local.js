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
   
   var currentHeight = null;
   var setHeight = function() {
      // Set the height of the iframe based on vanilla.
      var height = document.body.offsetHeight || document.body.scrollHeight;
      
      if (height != currentHeight) {
         Vanilla.parent.callRemote('height', height);
      }
   }
   
   window.onload = function() {
      setHeight();
      Vanilla.parent.callRemote('notifyLocation', window.location.href);
      
      setInterval(setHeight, 300);
   };
})(window, jQuery, Vanilla);