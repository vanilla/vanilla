# Foundation

Foundation is Vanilla's first asset-compatible theme. It defines the following assets:

-   `variables.json` - Defines color variables for the forum and knowledge base.
-   `footer.twig` - Footer of the theme.
-   `styles.(s)css` - Independantly scoped styles that apply to the footer (and header if it exists). See [Build instructions](#building-the-theme)

## Building the theme

Building this theme requires the following pre-requisites:

-   The theme is present in the `themes` directory of a Vanilla installation.
-   A Vanilla version of `4.0-2020.001` or later.
-   Node & yarn are setup in your development environment. See [prerequsite docs](https://success.vanillaforums.com/kb/articles/155-local-setup-quickstart) for installation instructions.
-   Vanilla's `node_modules` are installed. Run `yarn install` in the root of the Vanilla installation.

### The `src` directory

Files in the `src` directory use Vanilla's built-in build process. See [the Building Frontend Documentation](https://docs.vanillaforums.com/developer/tools/building-frontend/).

TL;DR:

-   To run a fast development build, that will watch for changes:
    -   Add `$Configuration['HotReload']['Enabled'] = true;` to your config.
    -   Run `yarn build:dev`.
    -   _Note: If you change your theme or addons, you will need to stop and restart this process._
-   To run a production build:
    -   Run `composer install` _or_ `yarn build`.
