/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import RichEditor from "../src/scripts/RichEditor";
const fs = require("fs");
const path = require("path");

describe("rendering", () => {
    let editor;

    beforeAll(() => {
        document.body.innerHTML = `
            <form>
                <div class="js-richText"></div>
                <textarea class="BodyBox"></textarea>
            </form>
        `;

        editor = new RichEditor(".js-richText");

        // Stub out this method not provided by JSDOM
    });

    const fixtureDir = path.resolve(__dirname, "../../../tests/fixtures/editor-rendering/");
    const fixturePaths = fs.readdirSync(fixtureDir);
    fixturePaths.forEach(fixture => {
        const testName = path.basename(fixture);

        test(testName, () => {
            const input = JSON.parse(fs.readFileSync(path.join(fixtureDir, fixture, "input.json"), "utf8"));
            const expectedOutput = fs.readFileSync(path.join(fixtureDir, fixture, "output.html"), "utf8");

            editor.editor.setContents(input);
            const richText = document.querySelector(".ql-editor");
            expect(richText.innerHTML).toEqual(expectedOutput.trim());
        });
    });
});
