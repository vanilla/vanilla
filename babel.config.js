/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

module.exports = (api, options) => {
    api.cache(true);
    let envPlugins = {};
    const isStoryshot = !!process.env.STORYSHOT;

    if (isStoryshot) {
        envPlugins["test"] = {
            plugins: ["require-context-hook"],
        };
    }

    return {
        presets: ["@vanilla/babel-preset"],
        env: {
            ...envPlugins
        }
    };
};
