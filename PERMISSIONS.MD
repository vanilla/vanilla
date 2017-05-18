To create a permission for a plugin or an application, below are the steps. This may be useful for creating a permission for something
you may want. For example, with the Yaga application created by notable member, hgtonight, I may want a permission for the Promote reaction.
Lucky for me, Garden allows me to go into the about.php file for the Yaga application to add in that new permissions.

1: If you're doing an application, about.php in the /settings folder is the place to go. For plugins, it will be class.theplugin.php
2: Open either file up depending on what you're using, using a code editor, like Notepad++.
3: When you see 'RegisterPermissions'  => array( go to the bottom of the last permission before the ) symbol and make it like this but changing the words (copy the marks as well): 'Application.Example.Example', // What does this permission do?
4: Save the files and upload the new about.php or class.yourplugin.php to your website
5: Disable and enable the plugin / applications.









