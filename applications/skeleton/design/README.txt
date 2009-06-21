By default, each application has it's own "design" folder for css and image
files off the root of that application (ie. /scaffolding/design/). If you wanted
to override them, you could copy them into your custom theme folder and edit
them there. 

If you want a view to be used across applications (ie. one master view for
vanilla, scffolding, etc), you could place it in:

/themes/theme_name/views/default.master
/themes/theme_name/design/*.png,*.css

If you wanted a view to be specific to one application (ie. an altered master
view for scaffolding only), you could place it in:

/themes/theme_name/app_name/views/default.master
/themes/theme_name/app_name/design/*.png,*.css