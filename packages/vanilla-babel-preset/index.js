/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

const { resolve } = require;

module.exports = api => {
    const isTest = api.env("test");
    const isJest = !!process.env.JEST;

    let envOptions = {
        useBuiltIns: false,
        modules: false,
    };

    const runtimePlugins = isTest || isJest
        ? []
        : [
              [
                  resolve("@babel/plugin-transform-runtime"),
                  {
                      useESModules: true,
                  },
              ],
          ];

    if ((process.env.NODE_ENV = "production" || process.env.DEV_COMPAT === "compat")) {
        envOptions.targets = "ie > 10, last 4 versions, not dead, safari 8";
    }

    if (isJest) {
        envOptions = {
            targets: {
                node: "current",
            }
        };
    }

    const preset = {
        presets: [
            [resolve("@babel/preset-env"), envOptions],
            resolve("@babel/preset-react"),
            resolve("@babel/preset-typescript"),
        ],
        plugins: [
            resolve("@babel/plugin-proposal-class-properties"),
            resolve("@babel/plugin-proposal-object-rest-spread"),
            resolve("@babel/plugin-syntax-dynamic-import"),
            resolve("@babel/plugin-proposal-nullish-coalescing-operator"),
            resolve("@babel/plugin-proposal-optional-chaining"),
            ...runtimePlugins,
        ],
    };

    return preset;
};
