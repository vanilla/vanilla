/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import {
    generateMockTable,
    tableWithMultipleColspansAndRowspans_json,
} from "@library/vanilla-editor/plugins/tablePlugin/tableFixtures";
import { setMeta } from "@library/utility/appUtils";
import { MyTableRowElement, MyValue } from "@library/vanilla-editor/typescript";
import {
    convertHeaders,
    getCellPosition,
    needsRowspanColspanCleaning,
} from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { ELEMENT_TD, ELEMENT_TH, ELEMENT_TABLE } from "@udecode/plate-table";
import { cleanTableRowspanColspan } from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { EElementOrText } from "@udecode/plate-common";
import { onKeyDownTable } from "@library/vanilla-editor/plugins/tablePlugin/onKeyDownTable";
import { vitest } from "vitest";

describe("Rich Table", () => {
    setMeta("featureFlags.RichTable.Enabled", true);

    // Mock events for keyboard navigation tests
    const mockTabEvent = {
        key: "Tab",
        shiftKey: false,
        preventDefault: vitest.fn(),
        stopPropagation: vitest.fn(),
        defaultPrevented: false,
    };

    const mockShiftTabEvent = {
        key: "Tab",
        shiftKey: true,
        preventDefault: vitest.fn(),
        stopPropagation: vitest.fn(),
        defaultPrevented: false,
    };

    const mockEscapeEvent = {
        key: "Escape",
        shiftKey: false,
        preventDefault: vitest.fn(),
        stopPropagation: vitest.fn(),
        defaultPrevented: false,
    };

    it("withNormalizeRichTable() - Convert first row cells of plain table into th if it is not th already", () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable();
        editor.insertNode(input);
        expect(editor.children).toStrictEqual(output);
    });

    it("withNormalizeRichTable() - If we have tbody, thead or tfoot we'll unwrap its content and strip those (we'll remove caption as well)", () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable({ hasCaption: true, hasHead: true, hasBody: true, hasFoot: true });
        editor.insertNode(input);
        expect(editor.children).toStrictEqual(
            output.map((element) => {
                if (element.type === "table") {
                    return { ...element, id: "table-1-0" };
                }
                return element;
            }),
        );
    });

    it("tableUtils - getCellPostion()", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        // Select the second cell in the third row
        editor.select({
            anchor: { path: [0, 2, 1, 0, 0], offset: 0 },
            focus: { path: [0, 2, 1, 0, 0], offset: 0 },
        });
        expect(getCellPosition(editor)).toStrictEqual({ row: 2, col: 1 });
    });

    it("tableUtils - convertHeaders()", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        editor.select({
            anchor: { path: [0, 2, 1, 0, 0], offset: 0 },
            focus: { path: [0, 2, 1, 0, 0], offset: 0 },
        });

        convertHeaders(editor, "left");

        editor.children[0].children.forEach((row, rowIndex) => {
            const firstCellInRow = (row as MyTableRowElement).children[0];
            const secondCellInRow = (row as MyTableRowElement).children[1];
            expect(firstCellInRow.type).toBe(ELEMENT_TH);
            expect(secondCellInRow.type).toBe(ELEMENT_TD);
        });

        convertHeaders(editor, "top");

        editor.children[0].children.forEach((row, rowIndex) => {
            if (rowIndex === 0) {
                (row as MyTableRowElement).children.forEach((cell) => {
                    expect(cell.type).toBe(ELEMENT_TH);
                });
            } else {
                (row as MyTableRowElement).children.forEach((cell, cellIndex) => {
                    expect(cell.type).toBe(ELEMENT_TD);
                });
            }
        });

        convertHeaders(editor, "both");

        editor.children[0].children.forEach((row, rowIndex) => {
            if (rowIndex === 0) {
                (row as MyTableRowElement).children.forEach((cell, cellIndex) => {
                    if (cellIndex === 0) {
                        expect(cell.type).toBe(ELEMENT_TD);
                    } else {
                        expect(cell.type).toBe(ELEMENT_TH);
                    }
                });
            } else {
                (row as MyTableRowElement).children.forEach((cell, cellIndex) => {
                    if (cellIndex === 0) {
                        expect(cell.type).toBe(ELEMENT_TH);
                    } else {
                        expect(cell.type).toBe(ELEMENT_TD);
                    }
                });
            }
        });
    });

    it("tableUtils - needsRowspanColspanCleaning() - Helper to determine if our table needs rowspan colspan cleaning", () => {
        const { input: inputNodesWithTable } = generateMockTable();

        const tableNeedsRowspanColspanCleaning = needsRowspanColspanCleaning([inputNodesWithTable] as unknown as Array<
            EElementOrText<MyValue>
        >);

        expect(tableNeedsRowspanColspanCleaning).toBe(false);

        const inputNodesWithMultipleColspansAndRowspans = JSON.parse(tableWithMultipleColspansAndRowspans_json);

        const tableNeedsRowspanColspanCleaning2 = needsRowspanColspanCleaning(
            inputNodesWithMultipleColspansAndRowspans as unknown as Array<EElementOrText<MyValue>>,
        );
        expect(tableNeedsRowspanColspanCleaning2).toBe(true);
    });

    it("tableUtils - cleanTableRowspanColspan() - Table with multiple colspans and rowspans, our initial normalization should clean those and insert empty cells to have the same cell number for each row", () => {
        const editor = createVanillaEditor();

        // includes a table with multiple colspans and rowspans, different number of cells in each row
        let inputNodes = JSON.parse(tableWithMultipleColspansAndRowspans_json);

        inputNodes = cleanTableRowspanColspan(editor, inputNodes as Array<EElementOrText<MyValue>>);
        const table = inputNodes.find((node) => node.type === ELEMENT_TABLE);

        // all rows have the same number of cells
        table.children[0].children.forEach((row) => {
            expect(row.children).toHaveLength(9);
        });

        // no cells with colspan or rowspan greater than 1
        table.children[0].children.forEach((row) => {
            row.children.forEach((cell) => {
                if (cell.attributes?.rowspan || cell.attributes?.colspan) {
                    const rowspanOrColspan = cell.attributes?.rowspan || cell.attributes?.colspan;
                    expect(rowspanOrColspan).toBe("1");
                }
            });
        });
    });

    it("onKeyDownTable() - Tab key navigates to next cell", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        // Start at first cell (0,0)
        editor.select({
            anchor: { path: [0, 0, 0, 0, 0], offset: 0 },
            focus: { path: [0, 0, 0, 0, 0], offset: 0 },
        });

        const initialPath = [...editor.selection!.anchor.path];

        const handler = onKeyDownTable(editor as any, { key: ELEMENT_TABLE } as any);
        handler(mockTabEvent as any);

        // Should move to next cell
        expect(editor.selection?.anchor.path).toEqual([0, 0, 1, 0, 0]);
    });

    it("onKeyDownTable() - Tab key creates new row when at last cell of table", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        const tableNode = editor.children[0] as any;
        const initialRowCount = tableNode.children.length;
        const lastRowIndex = initialRowCount - 1;
        const lastRow = tableNode.children[lastRowIndex];
        const lastCellIndex = lastRow.children.length - 1; // Get actual last cell index

        // Start at last cell of last row
        editor.select({
            anchor: { path: [0, lastRowIndex, lastCellIndex, 0, 0], offset: 0 },
            focus: { path: [0, lastRowIndex, lastCellIndex, 0, 0], offset: 0 },
        });

        const handler = onKeyDownTable(editor as any, { key: ELEMENT_TABLE } as any);
        handler(mockTabEvent as any);

        // Should have added a new row
        const newRowCount = (editor.children[0] as any).children.length;
        expect(newRowCount).toBe(initialRowCount + 1);
    });

    it("onKeyDownTable() - Shift+Tab key navigates to previous cell", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        // Start at second cell (0,1)
        editor.select({
            anchor: { path: [0, 0, 1, 0, 0], offset: 0 },
            focus: { path: [0, 0, 1, 0, 0], offset: 0 },
        });

        const handler = onKeyDownTable(editor as any, { key: ELEMENT_TABLE } as any);
        handler(mockShiftTabEvent as any);

        // Should move to previous cell
        expect(editor.selection?.anchor.path).toEqual([0, 0, 0, 0, 0]);
    });

    it("onKeyDownTable() - Shift+Tab key navigates to last cell of previous row when at first cell of row", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        // Start at first cell of second row (0,1,0)
        editor.select({
            anchor: { path: [0, 1, 0, 0, 0], offset: 0 },
            focus: { path: [0, 1, 0, 0, 0], offset: 0 },
        });

        const handler = onKeyDownTable(editor as any, { key: ELEMENT_TABLE } as any);
        handler(mockShiftTabEvent as any);

        // Should move to last cell of previous row (first row, last column)
        expect(editor.selection?.anchor.path).toEqual([0, 0, 5, 0, 0]);
    });

    it("onKeyDownTable() - Escape key exits table", () => {
        const editor = createVanillaEditor();
        const { input } = generateMockTable();
        editor.insertNode(input);

        // Start at any cell in the table
        editor.select({
            anchor: { path: [0, 0, 1, 0, 0], offset: 0 },
            focus: { path: [0, 0, 1, 0, 0], offset: 0 },
        });

        const initialPath = [...editor.selection!.anchor.path];

        const handler = onKeyDownTable(editor as any, { key: ELEMENT_TABLE } as any);
        handler(mockEscapeEvent as any);

        // Should exit table - move to position after table
        expect(editor.selection?.anchor.path[0]).not.toEqual(0);
        expect(editor.selection?.anchor.path[0]).toEqual(1);
    });
});
