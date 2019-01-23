# Theme Boilerplate


## Description

It's a  starter's kit to build Vanilla Forums custom themes. It contains all files necessary for a theme with customizable styles.

## Getting Started

Node and Yarn are prerequisites to use this tool. Please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/) and [yarn](https://yarnpkg.com/en/docs/install).

> **Notice**: It is important that you install Node in a way that does not require you to `sudo`.



1. Navigate to your theme folder and add the boilerplate package:

  ```
  yarn add @vanillaforums/theme-boilerplate
  ```

2. Run the installation command:

  ```
  yarn run boilerplate-install my-pretty-theme "My Pretty Theme"
  ```

  - Replace `my-pretty-theme` with your theme key. This should be unique and **must exactly match** the folder name, including capitalization. It should also follow the `dashed-lower-case` naming convention.
  - Replace `My Pretty Theme` with your theme name (keep the double quotes). This is the name that appears on the dashboard.

3. This tool also has a build step, provided by the [Vanilla CLI](https://docs.vanillaforums.com/developer/vanilla-cli/). This step is necessary to generate scripts and stylesheets that run in the browser.
   [Make sure the Vanilla CLI is installed](https://docs.vanillaforums.com/developer/vanilla-cli/installation), then run the following command on the theme folder to build:

  ```
  vanilla build
  ```

4. Your theme is ready to be enabled. On your localhost navigate to **Dashboard > Appearance > Themes** and enable your theme.

### Usage

You might want to start taking a look at `src/scss/_variables.scss`. There you can find most of the variables you need to customize your theme.

A good starting point is to create variables containing your brand values on the top of the `_variables.scss`, like colors, font family, sizes, etc. Make sure to use the  `$theme-` namespace to keep things organized.

Once your variables are set, you may start customizing!

To know more about the boilerplate SCSS structure please visit the [variables description](https://docs.vanillaforums.com/developer/theme-boilerplate/sctructure-variables/) page.
There you can find information about what each variable represents, naming conventions and folder structure.

Essentially you can overwrite whatever you like but be careful! Some variables are heavily inherited and not meant to be overwritten. Doing so may break your layout.

### Version

1.1.1 - Updates `_variables.scss`, make sure you update together with your variable sheet otherwise the build will not run.
1.1.0 - Updated `default.master.tpl` to solve security vulnerability.

### Building Styles / Javascript / Images

The boilerplate frontend assets are built with the [Vanilla CLI](https://docs.vanillaforums.com/developer/vanilla-cli/).
