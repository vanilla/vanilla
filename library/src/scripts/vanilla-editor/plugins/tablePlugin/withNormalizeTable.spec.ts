/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { waitFor } from "@testing-library/react";
import { TDescendant } from "@udecode/plate-headless";

const ROW_COUNT = 6;
const COL_COUNT = 6;
const CAPTION_TEXT = "Mock Table Caption";

describe("withNormalizeTable", () => {
    it("separate first row of plain table into thead and rest in tbody", async () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable();
        editor.insertNode(input);
        waitFor(() => {
            expect(editor.children).toStrictEqual(output);
        });
    });

    it("render provided caption and move first row of tbody to thead", async () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable({ hasCaption: true, hasBody: true });
        editor.insertNode(input);
        waitFor(() => {
            expect(editor.children).toStrictEqual(output);
        });
    });

    it("render provided caption, thead, tbody, and tfoot", async () => {
        const editor = createVanillaEditor();
        const { input, output } = generateMockTable({ hasCaption: true, hasBody: true, hasHead: true, hasFoot: true });
        editor.insertNode(input);
        waitFor(() => {
            expect(editor.children).toStrictEqual(output);
        });
    });
});

// generate table JSON
interface IRowOptions {
    length?: number;
    type?: string;
    isFoot?: boolean;
    isOutput?: boolean;
    label?: string;
}
const generateMockTableRow = (props: IRowOptions = {}): TDescendant[] => {
    const { length = 1, type = "td" } = props;
    const cellLabel = props.label ?? (props.isFoot ? "Footer" : props.type === "th" ? "Header" : "Cell");
    const children: TDescendant[] = Array(COL_COUNT)
        .fill(0)
        .map((_, idx) => {
            const subchildren: any[] = [];
            const textNode = {
                text: [cellLabel, idx + 1].join(" "),
            };
            if (props.isOutput) {
                subchildren.push({ type: "p", children: [textNode] });
            } else {
                subchildren.push(textNode);
            }

            return { type: type, children: subchildren };
        });

    return Array(length)
        .fill(0)
        .map(() => ({
            type: "tr",
            children,
        }));
};

interface IMockTableProps {
    hasCaption?: boolean;
    hasHead?: boolean;
    hasBody?: boolean;
    hasFoot?: boolean;
}
const generateMockTable = (props?: IMockTableProps) => {
    let inputChildren: TDescendant[] = [];
    let outputChildren: TDescendant[] = [];

    if (!props) {
        inputChildren = generateMockTableRow({ length: ROW_COUNT });
        outputChildren = [
            {
                type: "thead",
                children: generateMockTableRow({
                    type: "th",
                    label: "Cell",
                    isOutput: true,
                }),
            },
            {
                type: "tbody",
                children: generateMockTableRow({
                    length: ROW_COUNT - 1,
                    isOutput: true,
                }),
            },
        ];
    } else {
        if (props.hasCaption) {
            const caption = { type: "caption", children: [{ text: CAPTION_TEXT }] };
            inputChildren.push(caption);
            outputChildren.push(caption);
        }

        if (props.hasHead) {
            inputChildren.push({
                type: "thead",
                children: generateMockTableRow({ type: "th" }),
            });
            outputChildren.push({
                type: "thead",
                children: generateMockTableRow({ type: "th", isOutput: true }),
            });
        }

        if (props.hasBody) {
            inputChildren.push({
                type: "tbody",
                children: generateMockTableRow({ length: ROW_COUNT }),
            });
            if (props.hasHead) {
                outputChildren.push({
                    type: "tbody",
                    children: generateMockTableRow({ length: ROW_COUNT, isOutput: true }),
                });
            } else {
                outputChildren.push(
                    { type: "thead", children: generateMockTableRow({ type: "th", label: "Cell", isOutput: true }) },
                    { type: "tbody", children: generateMockTableRow({ length: ROW_COUNT - 1, isOutput: true }) },
                );
            }
        }

        if (props.hasFoot) {
            inputChildren.push({
                type: "tfoot",
                children: generateMockTableRow({ isFoot: true }),
            });
            outputChildren.push({
                type: "tfoot",
                children: generateMockTableRow({ isFoot: true, isOutput: true }),
            });
        }
    }

    return {
        input: { type: "table", children: inputChildren } as TDescendant,
        output: [
            { type: "table", children: outputChildren },
            { type: "p", children: [{ text: "" }] },
        ],
    };
};
