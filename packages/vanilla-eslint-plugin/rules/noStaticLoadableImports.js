/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const LOADABLE_IMPORT_REGEX = /.*\.loadable/;
const TEST_OR_STORY_REGEX = /.*\.(story|spec)/;
const IS_VANILLA_EDITOR_DIR = /.*\/vanilla-editor\//;

const messages = {
    avoidStaticLoadableImports: "Do not import statically from a loadable module. Use dynamic imports instead.",
};

const noStaticLoaddableImports = {
    name: "no-static-loadable-imports",
    meta: {
        type: "problem",
        docs: {
            description:
                "Detects moduels meant to be imported dynamically being imported statically for the Vanilla codebase.",
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
                const isLoadableImport = value.match(LOADABLE_IMPORT_REGEX);

                let fileName = context.getFilename();
                const isTestOrStory = fileName.match(TEST_OR_STORY_REGEX);
                const isVanillaEditor = fileName.match(IS_VANILLA_EDITOR_DIR);

                if (isLoadableImport && !isTestOrStory && !isVanillaEditor) {
                    context.report({
                        node,
                        messageId: "avoidStaticLoadableImports",
                    });
                    return;
                }
            },
        };
    },
};

module.exports = {
    noStaticLoaddableImports,
};
