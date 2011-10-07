jQuery(document).ready(function($) {
   
   $('.HomeOptions a').click(function() {
      var route = this.className;
      if (route == 'categoriesdiscussions')
         route = 'categories/discussions';
      else if (route == 'categoriesall')
         route = 'categories/all';
         
      $('#Form_Target').val(route);
      return false;
   });

});