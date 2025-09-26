/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
import { generateMockTable } from "@library/vanilla-editor/plugins/tablePlugin/tableFixtures";

describe("withNormalizeTable", () => {
    it("separate first row of plain table into thead and rest in tbody", () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable();
        editor.insertNode(input);
        expect(editor.children).toStrictEqual(output);
    });

    it("render provided caption and move first row of tbody to thead", () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable({ hasCaption: true, hasBody: true });
        editor.insertNode(input);
        expect(editor.children).toStrictEqual(output);
    });

    it("render provided caption, thead, tbody, and tfoot", () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable({ hasCaption: true, hasBody: true, hasHead: true, hasFoot: true });
        editor.insertNode(input);
        expect(JSON.stringify(editor.children, null, 4)).toEqual(JSON.stringify(output, null, 4));
    });
});
