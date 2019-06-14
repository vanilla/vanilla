/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import OpUtils from "@rich-editor/__tests__/OpUtils";
import { setupLegacyEditor } from "@rich-editor/__tests__/quillUtils";
import { expect } from "chai";
import Quill from "quill/core";
import { promiseTimeout } from "@vanilla/utils";

describe("EditorContent", () => {
    describe("legacyTextAreaSync", () => {
        it("can initialize from a value in the legacy text area.", async () => {
            const initialValue = [OpUtils.op("Test Header"), OpUtils.heading(2)];
            const { quill } = await setupLegacyEditor(initialValue);
            expect(quill.getContents().ops).deep.eq(initialValue);
        });

        it("can sync updates from quill to the textarea", async () => {
            const { quill, textarea } = await setupLegacyEditor([]);
            const valueToSet = [OpUtils.op("Test Header"), OpUtils.heading(2)];
            quill.setContents(valueToSet, Quill.sources.USER);
            await promiseTimeout(0); // 1 tick for form to update.
            expect(JSON.parse(textarea.value)).deep.eq(valueToSet);
        });
    });
});
