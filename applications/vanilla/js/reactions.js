jQuery(document).ready(function($) {

   $(document).on('click', '.Buried', function(e) {
      e.preventDefault();
      $(this).removeClass('Buried').addClass('Un-Buried');
//      console.log('buried click');
      return false;
   });

   $(document).on('click', '.Un-Buried', function() {
//      console.log('unburied click');
      $(this).removeClass('Un-Buried').addClass('Buried');
   });

   $(document).on('mouseenter', '.ReactButton', function() {
       var $button = $(this);

       if (gdn.definition('ShowUserReactions', false) != 'popup' || $('.Count', $button).length == 0 || !$button.data('reaction'))
           return;

       var itemID = $button.closest('.Item').attr('id');
       var $menu = $('.MenuItems-Reactions', $button);

       if ($menu.length == 0) {
            // Construct the initial div.
            $menu = $('<div class="MenuItems MenuItems-Reactions Up"><div class="TinyProgress" /></div>')
                .css('visibility', 'hidden')
                .attr('aria-hidden', 'true')
                .attr('tabindex', '-1')
                .appendTo($button);

            $.ajax({
                url: gdn.url('/reactions/users/'+itemID.split('_').join('/')+'/'+$button.data('reaction')),
                data: {DeliveryType: 'VIEW'},
                success: function(data) {
                    $menu.html(data);
                }
            });
       }

       // Position the menu above the reaction button.
       var left = ($button.outerWidth() - $menu.outerWidth()) / 2.0;
       var bottom = $button.height();

       $menu.css({ bottom: bottom, left: left, visibility: 'visible' });
   });

   $(document).on('mouseleave', '.ReactButton', function() {
       $('.MenuItems-Reactions', $(this)).css({visibility: 'hidden'});
   });

    $(document).on('click', '.MenuItems-Reactions a', function(event) {
        event.stopPropagation();
    });
});
