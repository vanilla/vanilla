/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

// @ts-check

const fs = require("fs");
const path = require("path");

const PLUGIN_NAME_REGEX = /^@([^\/]+)\/.*$/;
const PATH_ROOT = path.resolve(__dirname, "../../../");

const messages = {
    badCloudImport: "Don't import cloud addons in core. It breaks the OSS releases.",
};

const cloudPluginsPath = path.resolve(PATH_ROOT, "cloud", "plugins");
let cloudPlugins = [];
if (fs.existsSync(cloudPluginsPath)) {
    cloudPlugins = fs.readdirSync(path.resolve(cloudPluginsPath)).filter((dir) => ![".", ".."].includes(dir));
}

const noCloudImportsInCore = {
    name: "no-cloud-imports-in-core",
    meta: {
        type: "problem",
        docs: {
            description: "Detects cloud imports in core ",
            category: "Possible Errors",
        },
        messages,
    },
    create: function (context) {
        return {
            ImportDeclaration(node) {
                // something like "package-identifier/path/to/source";
                /**
                 *  @type {string} value
                 **/
                const value = node.source.value;
                const libraryMatch = value.match(PLUGIN_NAME_REGEX);
                if (!libraryMatch) {
                    return;
                }
                const potentialPluginName = libraryMatch[1];
                let fileName = context.getFilename();

                fileName = fs.realpathSync(fileName);

                if (!fileName.includes(cloudPluginsPath) && cloudPlugins.includes(potentialPluginName)) {
                    context.report({
                        node,
                        messageId: "badCloudImport",
                    });
                    return;
                }
            },
        };
    },
};

module.exports = {
    noCloudImportsInCore,
};
