/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import RichEditor from "../src/scripts/RichEditor";
const fs = require("fs");
const path = require("path");

describe("rendering", () => {
    let richEditor;

    beforeAll(() => {
        document.body.innerHTML = `
            <form>
                <div class="richEditor">
                    <div class="js-richText"></div>
                    <div class="js-richEditorInlineMenu"></div>
                    <div class="js-emojiHandle"></div>
                </div>
                <textarea class="BodyBox"></textarea>
            </form>
        `;

        const container = document.querySelector(".js-richText");

        richEditor = new RichEditor(container);

        // Stub out this method not provided by JSDOM
    });

    const fixtureDir = path.resolve(__dirname, "../../../tests/fixtures/editor-rendering/");
    const fixturePaths = fs.readdirSync(fixtureDir);
    fixturePaths.forEach(fixture => {
        const testName = path.basename(fixture);

        test(testName, () => {
            const input = JSON.parse(fs.readFileSync(path.join(fixtureDir, fixture, "input.json"), "utf8"));
            const expectedOutput = fs.readFileSync(path.join(fixtureDir, fixture, "output.html"), "utf8");

            richEditor.quill.setContents(input);
            const richText = document.querySelector(".ql-editor");
            expect(richText.innerHTML).toEqual(expectedOutput.trim());
        });
    });
});
