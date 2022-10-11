/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const path = require("path");
const fs = require("fs");
const glob = require("globby");
const VANILLA_ROOT = path.resolve(__dirname, "../../");

const sectionEnv = process.env.STORYBOOK_SECTION;
const hasModern = !sectionEnv || sectionEnv === "modern";
const hasFoundation = !sectionEnv || sectionEnv === "foundation";
const hasUI = !sectionEnv || sectionEnv === "ui";

const globs = [];

function scanAddons(addonDir) {
    const keys = glob
        .sync(path.join(VANILLA_ROOT, addonDir + "/*"), { onlyDirectories: true })
        .map((dir) => dir.replace(path.join(VANILLA_ROOT, addonDir + "/"), ""));

    keys.forEach((key) => {
        let root = path.join(VANILLA_ROOT, addonDir, key);
        if (fs.existsSync(path.join(root, "src"))) {
            globs.push(path.resolve(root, "src/**/*.story.@(ts|js|jsx|tsx|mdx)"));
        }
    });
}

if (hasModern) {
    globs.push(path.resolve(VANILLA_ROOT, "library/src/**/*.story.@(ts|js|jsx|tsx|mdx)"));
    scanAddons("applications");
    scanAddons("plugins");
}
if (hasFoundation) {
    globs.push(path.resolve(__dirname, "../entries/generatedStories.story.tsx"));
}
if (hasUI) {
    globs.push(path.resolve(VANILLA_ROOT, "packages/vanilla-ui/src/**/*.story.@(ts|js|jsx|tsx|mdx)"));
}

module.exports = {
    core: {
        // Needed until storybook 6.3 w/ webpack5 as the default.
        builder: "webpack5",
    },
    stories: globs,
    addons: [
        {
            name: "@storybook/addon-essentials",
            options: {
                actions: false,
                backgrounds: false,
                toolbars: false,
                controls: false,
            },
        },
    ],
    typescript: {
        check: false,
        reactDocgen: "none",
    },
};
