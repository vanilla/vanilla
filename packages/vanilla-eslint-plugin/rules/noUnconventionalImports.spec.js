/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const { RuleTester } = require("eslint");
const { noUnconventionalImports } = require("./noUnconventionalImports");

describe("handles detects invalid imports", () => {
    const tester = new RuleTester({
        parserOptions: {
            ecmaVersion: 6,
            sourceType: "module",
        },
    });

    const valid = ["@other/blah-blah/src/scripts/asdfasdf", "@vanilla/blah-blah/asdfasdf", "@libary/anything"];

    function makeImportText(path) {
        return `import thing from "${path}";`;
    }

    function makeInvalidImport(path) {
        const importCode = `import thing from "${path}";`;
        return {
            code: importCode,
            errors: [
                {
                    messageId: "avoidSrcScripts",
                },
            ],
        };
    }

    tester.run(noUnconventionalImports.name, noUnconventionalImports, {
        valid: valid.map(makeImportText),
        invalid: [
            {
                code: makeImportText("@vanilla/plugin-path/src/scripts/subpath"),
                errors: [
                    {
                        messageId: "avoidSrcScripts",
                    },
                ],
            },
            {
                code: makeImportText("@vanilla/library/src/scripts/some-path"),
                errors: [
                    {
                        messageId: "avoidLibrary",
                    },
                ],
            },
        ],
    });
});
