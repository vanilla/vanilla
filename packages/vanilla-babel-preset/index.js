/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

const { resolve } = require;

const envOptions = {
    useBuiltIns: false,
    modules: false,
}

if (
    process.env.NODE_ENV = "production" ||
    process.env.DEV_COMPAT === "compat"
) {
    envOptions.targets = "ie > 10, last 4 versions";
}

const preset = {
    presets: [
        [
            resolve("@babel/preset-env"),
            envOptions,
        ],
        resolve("@babel/preset-react"),
        resolve("@babel/preset-typescript"),
    ],
    plugins: [
        resolve("@babel/plugin-proposal-class-properties"),
        resolve("@babel/plugin-proposal-object-rest-spread"),
        resolve("@babel/plugin-syntax-dynamic-import"),
    ],
};

module.exports = () => preset;
