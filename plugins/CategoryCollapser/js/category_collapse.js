$(document).ready(function() {
   
   var getCookie = function(name) {
      var nameEQ = '__vn'+name + "=";
      var ca = document.cookie.split(';');
      for(var i=0;i < ca.length;i++) {
         var c = ca[i];
         while (c.charAt(0)==' ') c = c.substring(1,c.length);
         if (c.indexOf(nameEQ) == 0) {
            var result = c.substring(nameEQ.length,c.length).split('.');
            return result;
         }
      }
      return [];
   };
   
   var setCookie = function(name, value, days) {
      var expires = "";
      value = value.join('.');
      
      if (days) {
         var date = new Date();
         date.setTime(date.getTime()+(days*24*3600000)); // milliseconds per hour
         expires = "; expires="+date.toGMTString();
      }
      document.cookie = '__vn'+name+"="+value+expires+"; path=/";
   };
   
   var collapsed = getCookie('Collapsed');
   
   var expando = function(dontSave) {
      var $this = $(this);
      var $container = $this.closest('div');
      var $item = $('h2', $container).next();
      var id = $container.attr('id');
      
      if ($container.hasClass('Expando-Collapsed')) {
         $item.show();
         $container.removeClass('Expando-Collapsed');
         
         var index = collapsed.indexOf(id);
         if (index >= 0) {
            collapsed.splice(index, 1);
         }
      } else {
         $item.hide()
         $container.addClass('Expando-Collapsed');
         
         if (collapsed.indexOf(id) < 0)
            collapsed.push(id);
      }
      
      setCookie('Collapsed', collapsed, 365);
   };
   
   // Add collapserts to category groups.
   $('.CategoryGroup, #WhosOnline').each(function() {
      var id = $(this).attr('id');
      $('h2', this).append(' <span class="Expando" rel="'+id+'">+</span>');
      
      var isCollapsed = collapsed.indexOf(id) >= 0;
      if (isCollapsed) {
         $(this).addClass('Expando-Collapsed');
         $('h2', $(this)).next().hide();
      }
   });
   
   $('.Expando').click(expando);
});