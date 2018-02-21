/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "../Quill";
const fs = require("fs");
const path = require("path");

describe("rendering", () => {
    let quill;

    beforeAll(() => {
        document.body.innerHTML = `<form><div class="js-richText"></div></form>`;

        const container = document.querySelector(".js-richText");

        quill = new Quill(container);
    });

    const fixtureDir = path.resolve(__dirname, "../../../../../tests/fixtures/editor-rendering/");
    const fixturePaths = fs.readdirSync(fixtureDir);
    fixturePaths.forEach(fixture => {
        const testName = path.basename(fixture);

        test(testName, () => {
            let input = JSON.parse(fs.readFileSync(path.join(fixtureDir, fixture, "input.json"), "utf8"));
            let expectedOutput = fs.readFileSync(path.join(fixtureDir, fixture, "output.html"), "utf8");

            // Strip off extra whitespace
            expectedOutput = expectedOutput.replace(/\s+/, " ");

            quill.setContents(input);
            const richText = document.querySelector(".ql-editor");

            const editorOuput = richText.innerHTML.replace(/\s+/, " ");

            expect(editorOuput).toEqual(expectedOutput.trim());
        });
    });
});
