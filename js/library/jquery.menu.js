/**************************************************************
Menu Dropdown Plugin by Mark O'Sullivan
This plugin is a modified version of James Nylen's jQuery Menu Plugin v0.8.
Original credits below:

jQuery Menu Plugin v0.8
Copyright 2007-2008 James Nylen

Demo and Download:
http://hax.nylen.tv/jquery-menu/

Usage:

(1)----Make a menu like this:
<ul id="Menu">
   <li><a href="/dashboard/people/entry/signin" >Sign In</a></li>
   <li><a href="/dashboard/people/users" >People</a>
      <ul>
         <li><a href="/dashboard/people/user/add" >Add New</a></li>
         <li><a href="/dashboard/people/settings" >Settings</a></li>
      </ul>
   </li>
</ul>

You can also use different tag names than ul and li for the
menu items and submenus, but submenus still have to be
*direct children of their parent menu items*.

(2)----Write your stylesheet
You have to set up the menus' stylesheets and positioning
yourself, including any hover-related effects.  To help with
styling the menus, this plugin will add the following classes
to the specified menu items:
.First - the first item in a menu or submenu
.Last - the last item in a menu or submenu
.Parent - a menu item that contains a submenu
.Active - a parent item with an open submenu

(3)----Call the function
$('#Menu').menu();
-or-
$('#Menu').menu(options);
where options has one or more of the following properties:
{
  showOnClick: [1 or 0 indicating if the submenu should show
               on click (1) or hover (0)],
  showDelay: [milliseconds to wait before showing a menu],
  switchDelay: [milliseconds to wait before switching to a
                different submenu on the same level],
  hideDelay: [milliseconds to wait before hiding a menu],
  itemSel: [selector that matches a menu item (default is li)],
  menuSel: [selector that matches a submenu (default is ul)],
  show: function() {
    //code to show a submenu
    //called with this == a submenu (ul or menuSel element)
    //by default, just sets this.visibility to visible
  }
  hide: function() {
    //code to hide a submenu
    //called with this == a submenu (ul or menuSel element)
    //by default, just sets this.visibility to hidden
  }
}
All properties are optional.  Delays default to 0ms.  You
should not use child or descendant selectors with itemSel and
menuSel, since itemSel and menuSel are always passed to the
jQuery children() function.

**************************************************************/

(function($) {
  $.fn.menu = function(opt) {
    opt = $.extend({
      showOnClick: 0,
      showDelay: 0,
       switchDelay: 0,
       hideDelay: 0,
       menuSel: 'ul',
       itemSel: 'li',
       show: function() {
        $(this).show();
         this.style.visibility = 'visible';
       },
       hide: function() {
        $(this).hide();
         this.style.visibility = 'hidden';
       }
    }, opt);
    hideAll = function(e) {
      // $('#Debug').html($('#Debug').html() + '<br />hide');
      $(e.data).removeClass('Active');
      opt.hide.call($(e.data).children(opt.menuSel).get(0));
      $('*').unbind('click', hideAll);
    }
     setTo = function(action, time) {
        var o = this;
        $(o).attr('pending', action);
        window.setTimeout(function() {
           if($(o).attr('pending') == action) {
              if (action == 'show') {
                 $(o).parent().addClass('Active');
                 opt.show.call(o);
              } else {
                 $(o).parent().removeClass('Active');
                 opt.hide.call(o);
              }
           }
        }, time);
     };
     $(this).children(opt.itemSel).each(function(i) {
      // Add the "first" css class to the first menu item
        if (i == 0)
        $(this).addClass('First');
        
      // Add the "last" css class to the last menu item
      if (i == $(this).parent().children(opt.itemSel).length-1)
        $(this).addClass('Last');
        
      // If there are children under the current item, show them on hover (and
      // hide on blur)
        if ($(this).children(opt.menuSel).length) {
        $(this).addClass('Parent');
        if (opt.showOnClick == 1) {
          var row = this;
          // Show/Hide on click
          $(this).children('strong').click(function() {
            // $('#Debug').html($('#Debug').html() + '<br />show');
            $(row).addClass('Active');
            opt.show.call($(row).children(opt.menuSel).get(0));
            
            // Bind a click event to the body so that when anything else is
            // clicked, the menu disappears. "one" will cause the event to
            // unbind after the first time it fires. Note: I had to put a
            // setTimeout on this bind so that the click wouldn't fire
            // immediately.
            setTimeout(function() {
              $('*').bind('click', row, hideAll);
            }, 10);
            return false;
          });
        } else {
          if ($(this).children(opt.menuSel).length)
            $(this).addClass('Parent').hover(function() {
              var o = this;
              $(this).parent().children('.Active').each(function() {
                if (this != o)
                  setTo.call($(this).children(opt.menuSel).get(0), 'hide', opt.switchDelay);
              });
              setTo.call($(this).children(opt.menuSel).get(0), 'show', $(this).parent().children('.Active').length ? opt.switchDelay : opt.showDelay);
            }, function() {
              setTo.call($(this).children(opt.menuSel).get(0), 'hide', opt.hideDelay);
            });
        }
        $(this).children(opt.menuSel).each(function() {
          $(this).menu(opt);
        });
      }
    });
    return $(this);
  }
})(jQuery);