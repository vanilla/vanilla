# Keystone


## Description

A responsive Vanilla theme with customization options.


## Customizations

- Javascript to animate the open and close of the mobile navigation menu.
- Sets its own flyout open/close listeners.
- Dependency on the [theme-boilerplate](https://www.npmjs.com/package/@vanillaforums/theme-boilerplate).
- Overwrites the `themeOptions_create` to support `hasHeroBanner`, `hasFeatureSearchbox` and `panelToLeft` customized options.
- Custom options `hasHeroBanner`, `hasFeatureSearchbox` are dependent on the Hero Image Plugin.
- If Advanced Search Plugin is enabled, the theme will render advanced search module, otherwise, it will render the search module.

## Building Styles / Javascript / Images

This theme is built with the [Vanilla Cli](https://docs.vanillaforums.com/developer/vanilla-cli/) and does not come with its own build toolchain. With that installed you can simply run:
```bash
vanilla build
```
or
```bash
vanilla build --watch
```
to build your styles/js/images. Documentation for the CLI can be found [here](https://docs.vanillaforums.com/developer/vanilla-cli/#build-tools).
