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
   
   window.onload = function() {
      // Set the height of the iframe based on vanilla.
      var height = document.body.offsetHeight || document.body.scrollHeight;
      
      Vanilla.parent.callRemote('height', height);
      
      Vanilla.parent.callRemote('notifyLocation', window.location.href);
   }; 

})(window, jQuery, Vanilla);