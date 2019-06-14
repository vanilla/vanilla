# @vanillaforums/babel-preset

> A babel preset for transforming your JavaScript for Vanilla Forums.

Currently contains transforms for all standard syntax that is [stage 4](https://tc39.github.io/ecma262/) (ES2017).

We have also enabled the following additional plugins:

-   [Rest/Spread Properties](https://babeljs.io/docs/en/babel-plugin-proposal-object-rest-spread)
-   [React JSX support](https://babeljs.io/docs/en/next/babel-preset-react)
-   [Babel Typescript](https://babeljs.io/docs/en/babel-preset-typescript)
-   [ES Dynamic Import Syntax (actual import is provided by webpack)](https://babeljs.io/docs/en/babel-plugin-syntax-dynamic-import/)
-   [Class Properties](https://babeljs.io/docs/en/babel-plugin-proposal-class-properties)

## Install

```sh
$ yarn add --dev @vanilla/babel-preset
```

## Usage

### Via `.babelrc` (Recommended)

**.babelrc**

```json
{
    "presets": ["@vanilla/babel-preset"]
}
```

### Targeting Environments

This module uses @babel/preset-env to target specific environments.

For a list of browsers please see [browserlist](https://github.com/ai/browserslist).

Currently targetted environments are:

```js
["ie > 10", "last 4 versions"];
```
