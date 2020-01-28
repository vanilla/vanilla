# Custom Foundation Theme

**_Note: This theme MUST be installed into the `/addons/themes` directory of your installation. The `/themes` directory WILL NOT work._**

Foundation is Vanilla's first asset-compatible theme. It defines the following assets:

-   `variables.json` - Defines color variables for the forum and knowledge base.
-   `footer.twig` - Footer of the theme.
-   `styles.(s)css` - Independantly scoped styles that apply to the footer (and header if it exists). See [Build instructions](#building-the-theme)

## Building the theme

Building this theme requires the following pre-requisites:

-   The theme is present in the `themes` directory of a Vanilla installation.
-   A Vanilla version of `4.0-2020.001` or later.
-   Node & yarn are setup in your development environment. See [prerequsite docs](https://success.vanillaforums.com/kb/articles/166-vanillas-frontend-build-system#prerequisites) for installation instructions.
-   Vanilla's `node_modules` are installed. Run `yarn install` in the root of the Vanilla installation.

### The `src` directory

Files in the `src` directory use Vanilla's built-in build process. See [the Building Frontend Documentation](https://success.vanillaforums.com/kb/articles/166-vanillas-frontend-build-system).

TL;DR:

-   To run a fast development build, that will watch for changes:
    -   Add `$Configuration['HotReload']['Enabled'] = true;` to your config.
    -   `cd` into the root of your vanilla installation.
    -   Run `yarn build:dev`.
-   To run a production build:
    -   `cd` into the root of your vanilla installation.
    -   Run `composer install` _or_ `yarn build`.

### The `styles.scss` asset

This asset is _not_ integrated into prodcution builds of the core process.

As such while working on the isolated header/footer stylesheet, it can be rebuild as follows

-   Make sure the theme is symlinked into your Vanilla installation.
-   To build it once: `yarn workspace my-theme-key build`
-   To build it and watch for changes: `yarn workspace my-theme-key build --watch`

## Troubleshooting guide

### Yarn scripts don't work when I'm in the theme

Foundation based themes are meant to be part of Vanilla's workspace. If your theme is symlinked into Vanilla, some shells will automatically resolve the symlink when navigating.

For example

```
vanilla
  - node_modules/.bin/sass
  - addons/themes
    - theme-my-custom --> Symlink to otherRepo/themes/theme-my-custom
otherRepo
  - themes
    - theme-my-custom
```

Unfortunately if your shell changes your directy into `otherRepo` it can't resolve the node_modules over in Vanilla.

To resolve this your should use yarn workspace commands:

```
# Do this
yarn workspace theme-my-custom build

# Do NOT do this
cd addons/themes/theme-my-custom
yarn build
```
