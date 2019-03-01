Welcome to the Vanilla Dashboard Styleguide!

This styleguide covers the Vanilla dashboard styling.

The goals of this styleguide are

* to showcase the components we use in the dashboard, so you can easily mix-and-match to build your own settings page;
* to ensure a streamlined appearance;
* to ensure a reliable and expected UX;
* to cross-reference PHP rendering helpers;
* to document our process/workflow and dependencies.

---

## Tips and Tricks

### Avoid going rogue when building your own settings page

Try not to add css, whether it's inline, or contained in an internal or external stylesheet.
Optimally, we want to have all of the dashboard's css in the admin.css file.
This encourages developers to use existing patterns. If a necessary pattern doesn't exist,
we can build it with the help of our design team (aka Dan).

### Borders

Many of the dashboard components have borders. This can make it tricky to ensure that there are no double-borders
when elements are flush against eachother. To fix this, if possible only add border-bottom. If a top border is
necessary, add a margin of -(border-width) to the element. There's a helpful mixin (`has-border-top`) that does just
that and makes it clear what you're doing.

---

## Workflow

This section covers our workflow. This includes adding/removing/updating vendor dependencies, concatinating javascript,
compiling css, etc.

### Grunt Tasks

Grunt is our task manager. It provides us with a bunch of tools to accomplish a variety of tasks. In the future,
we may wish to look into using npm as a build tool instead.

Here are our Grunt tasks:

* sass_globbing - Creates scss files with maps to a directory's scss files. This allows us to add or remove scss files
  without having to worry about including them or removing them from any stylesheet.
* scsslint - Gives us hints about best practices in our Sass. It's run with the force flag, so the Sass will compile
  even if the linting fails. All the same, it's good to heed the linter where possible and eliminate notices.
* sass - Compiles our Sass into CSS and creates a source map.
* autoprefixer - Adds vendor prefixes to the CSS and updates the source map.
* concat - Concats files in js/src to js/dashboard.js. It also concats all the js we need for the styleguide.
* jshint - Gives us hints about best practives in our javascript in the js/src directory.
* imagemin - Minifies images in design/images

For any Grunt task, you can run `grunt *` to run the task. Simply running `grunt` will run all the above tasks.

#### Special Tasks

* watch - Runs through all the above tasks when our Sass or js/src/*.js files are saved. Also auto refreshes your browser
  if you have the livereload extension installed.
* styleguide - Concats and copies CSS and js assets and generates the styleguide from applications/dashboard/template
  to applications/dashboard/styleguide.
* wiredep - Copies vendor assets from the bower_components directory to its final resting place in Vanilla. You'll
  need to run this if you update the version of a dependency.

### Dependency Managers

#### Bower

Bower is the dependency manager for our frontend vendor code. The current dependencies and their versions are listed in
bower.json in the dashboard application's root.

##### Add a Bower Dependency

1. Run `bower install --save my_dependency_name`
2. Copy the files you need to their appropriate home in Vanilla by adding to the `copy` task in the Gruntfile.
3. Run `grunt wiredep` to copy the files over.

#### npm

npm is the dependency manager for our process/workflow. It takes care of installing grunt, grunt tasks and bower. The
full list of dependencies can be found in the package.json file in the dashboard application's root.

#### Do We Need Two Dependency Managers?

Maybe not. We could probably accomplish everything with npm. However, it's nice to have the separation of frontend
code and process code that having the two dependency managers provides. It may be something we want to revisit in the
future.

---

## Vendor Dependencies

The following vendor dependencies are managed by Bower.

### Twitter Bootstrap (twbs)

We use a subset of [Bootstrap 4](https://v4-alpha.getbootstrap.com/getting-started/introduction/) in the frontend.

#### twbs Sass

The specific scss files we use are listed here.

* normalize.scss
* reboot.scss
* utilities.scss
* nav.scss
* tooltip.scss
* dropdown.scss
* list-group.scss
* forms.scss
* grid.scss

#### twbs Javascript

The specific javascript files we use are listed here.

* collapse.js - For the collapsing nav in the panel.
* dropdown.js - For our dropdown menus.
* modal.js - For our modal display.
* tooltip.js - For tooltips (used in the embed section for copying and in the analytics section for pinning).
* util.js - twbs transition helpers.

### Other Vendor Javascript

* DateRangePicker - For the analytics date range picker.
* Ace - For the code editor in the Pockets and Customize Theme plugins.
* Google Prettify - For syntax highlighting to our code and pre blocks.
* iCheck - For our radio and checkbox styling.
* Drop - For our user card dropdown.
* Clipboard - For copying text. (Used in the embed section.)
* CheckAll - For checking/unchecking a set of checkboxes at the same time.

---

## Other Dependencies

Other than the vendor js above, we also rely on the following js:

in `/applications/dashboard/js`:

* jquery.tablejenga.js - Table Jenga handles responsiveness by translating table columns into meta data.
  and scrolls down when the user scrolls down.
* colorpicker.js - For color picker form inputs.
* cropimage.js - For image cropping.

in core `/js/library`:

* jquery.gardencheckboxgrid.js - For the checkbox grid (for permission tables).
* jquery.expander.js - Truncates text and adds a read more link after a number of characters.
* jquery.form.js - For ajax forms.

in core `/js`:

* global.js - For our gdn* functions, contentLoad, etc.

