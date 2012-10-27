(function(window) {
   // Check to see if we are even embedded.
   if (window.top == window.self)
      return;
   
   window.onload = function() {
      // Set the height of the iframe based on vanilla.
      var height = document.body.offsetHeight || document.body.scrollHeight;
      
      parent.socket.callRemote('height', height);
      
      parent.socket.callRemote('notifyLocation', window.location.href);
   }; 

})(window);