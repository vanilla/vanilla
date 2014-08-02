How to create a theme:

Part 1: CSS / Design
====================================================

1. Copy this "default" theme folder and rename it to your new theme name.
2. Open the "about.php" file and edit the information to reflect your theme. Be
   sure to change the array key like this: $ThemeInfo['YourThemeNameHere'].
3. Create a "design" subfolder and add a "custom.css" file to it. 
   Use it to selectively override CSS - it is called after the default styles.
4. Create a "views" subfolder and copy "/applications/dashboard/views/default.master.tpl" to it.
5. Go to your Dashboard, Themes, and apply your new theme.

If you want to edit the look & feel of the administrative screens, also
  add a design/customadmin.css in step 3.


Part 2: HTML / Views (Advanced)
====================================================

If you want to customize the HTML, you can edit that too. 
Our pages are made up of two parts:

 1. Master Views - these represent everything that wraps the main content of the
   page. If all you want to do is add a menu or banner above Vanilla, this is
   all you need to alter. To do so, copy the default master view from
   /applications/dashboard/views/default.master.php to
   /themes/yourtheme/views/default.master.php and edit it there.
   
 2. Views - these represent all of the content in each page. Every application
   has a "views" folder that contains all of the HTML for every page. So, for
   example, if you wanted to edit the HTML for the Discussions list, you could
   copy the views from /applications/vanilla/views/discussions to
   /themes/yourtheme/views/discussions and edit them there.

You can avoid naming conflicts between applications' views and specify which 
app a view is for by optionally adding a subfolder with the app's name in 
/themes/yourtheme/views/ (e.g.: /themes/yourtheme/views/appname/) and placing 
views there rather than directly in the "views" folder.