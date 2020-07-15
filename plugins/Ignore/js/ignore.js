jQuery(document).ready(function($){

   $('.Ignored').each(function(i,el){
      $(el).addClass('IgnoreHide');
   });

   $(document).on('click', '.Ignored', function(event) {
      var el = $(event.target);
      if (!el.hasClass('Ignored'))
         el = el.closest('.Ignored');

      if (el.hasClass('IgnoreHide'))
         el.removeClass('IgnoreHide');
      else
         el.addClass('IgnoreHide');
   });

})