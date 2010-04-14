How to create a theme:

Part 1: CSS / Design
================================================================================
1. Copy this "default" theme folder and rename it to your new theme name.
2. Open the "about.php" file and edit the information to reflect your theme. Be
   sure to change the array key like this: $ThemeInfo['YourThemeNameHere'].
3. Create a "design" subfolder and copy /applications/dashboard/design/style.css 
   and /applications/vanilla/design/vanilla.css into it.
4. Any background images you want to continue using (like the star png images
   for bookmarking) should be copied along with their respective stylesheets.
5. Go to your Dashboard, Themes, and apply your new theme.
6. Edit the copied CSS files to look however you wish!

Other things you should know:

 + All non-forum pages should be edited in the global "style.css" file.

 + If you want to edit the look & feel of the administrative screens, also 
   copy /applications/dashboard/design/admin.css in step 3. Similarly, you can 
   copy other CSS files like /applications/vanilla/design/vanillaprofile.css to
   customize those pages as well.


Part 2: HTML / Views
================================================================================
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