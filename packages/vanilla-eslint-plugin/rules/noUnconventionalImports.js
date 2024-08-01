/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const VANILLA_LIBRARY_IMPORT_REGEX = /@vanilla\/library\/src\/scripts\/(.*)/;
const SRC_SCRIPTS_IMPORT_REGEX = /@vanilla\/(.*)\/src\/scripts\/(.*)/;

const messages = {
    avoidLibrary: "Avoid using paths with @vanilla/library. This normally indicates your IDE is misconfigured",
    rewriteLibraryPath: `Use "@library" instead of "@vanilla/library/src/scripts".`,
    avoidSrcScripts: "Avoid using paths with src/scripts. This normally indicates your IDE is misconfigured",
    rewritePluginPath: `use "@PLUGIN/PATH" instead of "@vanilla/PLUGIN/src/scripts/PATH".`,
};

const noUnconventionalImports = {
    name: "no-unconventional-imports",
    meta: {
        type: "problem",
        docs: {
            description: "Detects unconvetional imports for the Vanilla codebase.",
            category: "Possible Errors",
        },
        messages,
    },
    create: function (context) {
        return {
            ImportDeclaration(node) {
                // something like "package-identifier/path/to/source";
                /** @var {string} value */
                const { value } = node.source;
                const libraryMatch = value.match(VANILLA_LIBRARY_IMPORT_REGEX);
                if (libraryMatch) {
                    context.report({
                        node,
                        messageId: "avoidLibrary",
                        suggest: [
                            {
                                messageId: "rewriteLibraryPath",
                                fix: function (fixer) {
                                    const [path] = libraryMatch;
                                    const newPath = `@library/${path}`;
                                    return fixer.replaceText(node, newPath);
                                },
                            },
                        ],
                    });
                    return;
                }

                const srcScriptsMatch = value.match(SRC_SCRIPTS_IMPORT_REGEX);
                if (srcScriptsMatch) {
                    context.report({
                        node,
                        messageId: "avoidSrcScripts",
                        suggest: [
                            {
                                messageId: "rewritePluginPath",
                                fix: function (fixer) {
                                    const [plugin, path] = srcScriptsMatch;
                                    const newPath = `@vanilla/${plugin}/${path}`;
                                    return fixer.replaceText(node, newPath);
                                },
                            },
                        ],
                    });
                }
            },
        };
    },
};

module.exports = {
    noUnconventionalImports,
};
