/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

const { resolve } = require;

module.exports = (api, options) => {
    const modernBrowserList =
        "Edge >= 83, Firefox >= 78, FirefoxAndroid  >= 78, Chrome >= 80, ChromeAndroid >= 80, Opera >= 67, OperaMobile >= 67, Safari >= 13.1, iOS >= 13.4";
    const isJest = !!process.env.JEST;

    let envOptions = {
        useBuiltIns: false,
        modules: "auto",
    };

    let runtimePlugins = [];

    if (isJest) {
        envOptions = {
            targets: {
                node: "current",
            },
        };
    } else {
        // Modern targets
        envOptions.targets = modernBrowserList;
    }

    const preset = {
        sourceType: "unambiguous",
        presets: [
            [resolve("@babel/preset-env"), envOptions],
            [resolve("@babel/preset-react", { useBuiltIns: true })],
            resolve("@babel/preset-typescript"),
        ],
        plugins: [
            [
                "@emotion",
                {
                    autoLabel: "always",
                    labelFormat: "[filename]-[local]",
                },
            ],
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
