By default, each application has it's own "views" folder for page-specific php
and xhtml code. This folder is located off the root of that application
(ie. /people/views/). Most of the time any customization you want to make
will be done on a "Master" view, which is the frame in which all views are
displayed. If you want to override *any* type of view, you can copy the ones
you want to alter into your custom theme folder and edit them there. 

If you want a view to be used across applications (ie. one master view for
vanilla, scaffolding, etc), you could place it in:

/themes/theme_name/views/default.master
/themes/theme_name/design/*.png,*.css

If you wanted a view to be specific to one application (ie. an altered master
view for vanilla only), you could place it in:

/themes/theme_name/app_name/views/default.master
/themes/theme_name/app_name/design/*.png,*.css