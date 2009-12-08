How to create a theme:

Part 1: CSS / Design
================================================================================
1. Copy this "default" theme folder and rename it to your new theme name.
2. Open the "about.php" file and edit the information to reflect your theme.
3. Grab the style.css file from /applications/garden/design/ folder and copy it
   into your theme's "design" folder. Do the same for the
   /applications/vanilla/design/vanilla.css file.
4. Go to your Dashboard, Themes, and apply your new theme.
5. Edit the copied css files to look however you wish!

Other things you should know:

 + All non-Vanilla pages should be edited in the global "style.css" file.
   
 + If you want to edit the look & feel of the garden/administrative screens, you
   can accomplish it by copying the /applications/garden/design/admin.css file
   to /themes/yourtheme/design/admin.css and editing it there.
   /

Part 2: HTML / Views
================================================================================
If you don't like the way we've structured our Html, you can edit that too. Our
pages are made up of two parts:

 1. Master Views - these represent everything that wraps the main content of the
   page. If all you want to do is add a menu or banner above Vanilla, this is
   all you need to alter. To do so, copy the default master view from
   /applications/garden/views/default.master.php to
   /themes/yourtheme/views/default.master.php and edit it there.
   
 2. Views - these represent all of the content in each page. Every application
   has a "views" folder that contains all of the html for every page. So, for
   example, if you wanted to edit the html for the discussion list, you could
   copy the views from /applications/vanilla/views/discussions to
   /themes/yourtheme/views/discussions and edit them there.