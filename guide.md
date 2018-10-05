## Vanilla-Theme-Boilerplate

### What it is

It's a  starter's kit to build Vanilla Forums responsive themes. It contains all files necessary for a theme with customizable styles.

### How it works

Styles are written in [SASS](https://sass-lang.com/) and has most of its styles bases in SASS variables and inheritance. Variables are carried through sections and components making the final styles flexible but also consistent throughout the site.

### How to use it

Node and Yarn are prerequisites to run this tool. Please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/) and [yarn](https://yarnpkg.com/en/docs/install).

> **Notice**: It is important that you install Node in a way that does not require you to `sudo`.



1. Navigate to your theme folder and add the boilerplate package:

```
yarn add vanilla-theme-boilerplate
```

2. Run the installation command:

```
yarn run boilerplate-install my-pretty-theme "My Pretty Theme"
```

- Replace `my-pretty-theme` with your theme key. This should be unique and **must exactly match** the folder name, including capitalization. It should also follow the `dashed-lower-case` naming convention.
- Replace `My Pretty Theme` with your theme name (keep the double quotes). This will appear on the dashboard.

3. This theme has a build step, provided by the [Vanilla CLI](https://docs.vanillaforums.com/developer/vanilla-cli). This step is necessary to generate scripts and stylesheets that run in the browser.
   [Make sure the Vanilla CLI is installed](https://docs.vanillaforums.com/developer/vanilla-cli/installation), then run the following command to build:

```
vanilla build
```

4. Your theme is ready to be enabled. On your localhost navigate to `Dashboard > Appearance > Themes` and enable your theme.

### Getting started

You might want to start taking a look at `src/scss/variables.scss`. There you can find most of the variables you need to customize your theme.

A good starting point is to create variables containing your brand values on the top of the `_variables.scss`, like colors, font family, sizes, etc. Make sure to use the  `$theme-` namespace to keep things organized.

Some interesting reading material if you don't know [how SASS variables works](https://sass-lang.com/guide), and [CSS inheritance](https://developer.mozilla.org/en-US/docs/Web/CSS/inherit).

Once your variables are set, you may start customizing!

To know more about the boilerplate CSS structure you can read this [descriptive guide](https://github.com/vanilla/themes/blob/master/vanilla-theme-boilerplate/sections-and-variables.md) of what some variables represents, naming conventions and folder structure.

Essentially you can overwrite whatever you like but be careful! Some variables are heavily inherited and not meant to be overwritten. Doing so may break your layout.


### Building Styles / Javascript / Images

The boilerplate is built with the [Vanilla Cli](https://docs.vanillaforums.com/developer/vanilla-cli/) and does not come with its own build toolchain. With that installed you can simply run:

```bash
vanilla build
```
or
```bash
vanilla build --watch
```
to build your styles/js/images. Documentation for the CLI can be found [here](https://docs.vanillaforums.com/developer/vanilla-cli/#build-tools).
