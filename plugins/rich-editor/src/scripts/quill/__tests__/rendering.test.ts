/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "../../Quill";
import * as fs from "fs";
import * as path from "path";

describe("rendering", () => {
    let quill;

    beforeAll(() => {
        document.body.innerHTML = `<form><div class="js-richText"></div></form>`;

        const container = document.querySelector(".js-richText");

        quill = new Quill(container as HTMLElement);
    });

    // The MutationObserver shim we're using allows us to run the tests, but prevents proper optimization, making
    // Actual rendering in the browser and what runs in testing different. These tests will be skipped until we
    // Move to do integration testing.
    const skipDueToBadMutationObserverShim = ["spoiler", "all-blocks"];

    const skip = [...skipDueToBadMutationObserverShim, ".editorconfig"];

    const fixtureDir = path.resolve((global as any).VANILLA_ROOT, "./tests/fixtures/editor-rendering/");
    const fixturePaths = fs.readdirSync(fixtureDir).filter(item => !skip.includes(item));
    fixturePaths.forEach(fixture => {
        const testName = path.basename(fixture);

        test(testName, () => {
            const input = JSON.parse(fs.readFileSync(path.join(fixtureDir, fixture, "input.json"), "utf8"));

            const clientSpecificOutputPath = path.join(fixtureDir, fixture, "output-client.html");
            const genericOutputPath = path.join(fixtureDir, fixture, "output.html");
            let expectedOutput;

            if (fs.existsSync(clientSpecificOutputPath)) {
                expectedOutput = fs.readFileSync(clientSpecificOutputPath, "utf8");
            } else {
                expectedOutput = fs.readFileSync(genericOutputPath, "utf8");
            }

            // Strip off extra whitespace
            expectedOutput = expectedOutput.replace(/\s+/, " ");

            quill.setContents(input);

            const richText = document.querySelector(".ql-editor");

            const editorOuput = richText!.innerHTML.replace(/\s+/, " ");

            expect(editorOuput).toEqual(expectedOutput.trim());
        });
    });
});
