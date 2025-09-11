/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";
import { TDescendant } from "@udecode/plate-common";

const ROW_COUNT = 6;
const COL_COUNT = 6;
const CAPTION_TEXT = "Mock Table Caption";

// generate table JSON
interface IRowOptions {
    length?: number;
    type?: string;
    isFoot?: boolean;
    isOutput?: boolean;
    label?: string;
}
export const generateMockTableRow = (props: IRowOptions = {}): TDescendant[] => {
    const { length = 1, type = "td" } = props;
    const cellLabel = props.label ?? (props.isFoot ? "Footer" : props.type === "th" ? "Header" : "Cell");
    const children: TDescendant[] = Array(COL_COUNT)
        .fill(0)
        .map((_, idx) => {
            const subchildren: any[] = [];
            const textNode = {
                text: [cellLabel, idx + 1].join(" "),
            };
            subchildren.push({ type: "p", children: [textNode] });

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
export const generateMockTable = (props?: IMockTableProps) => {
    const isRichTableEnabled = getMeta("featureFlags.RichTable.Enabled", false);

    let inputChildren: TDescendant[] = [];
    let outputChildren: TDescendant[] = [];

    if (!props) {
        inputChildren = generateMockTableRow({ length: ROW_COUNT });
        outputChildren = isRichTableEnabled
            ? [
                  ...generateMockTableRow({
                      type: "th",
                      label: "Cell",
                      isOutput: true,
                  }),
                  ...generateMockTableRow({ length: ROW_COUNT - 1, isOutput: true }),
              ]
            : [
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
        if (isRichTableEnabled) {
            inputChildren = generateMockTableRow({ length: ROW_COUNT });
            outputChildren = [
                ...generateMockTableRow({
                    type: "th",
                    label: "Cell",
                    isOutput: true,
                }),
                ...generateMockTableRow({ length: ROW_COUNT - 1, isOutput: true }),
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
                        {
                            type: "thead",
                            children: generateMockTableRow({ type: "th", label: "Cell", isOutput: true }),
                        },
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
    }

    return {
        input: { type: "table", children: inputChildren } as TDescendant,
        output: [
            { type: "table", children: outputChildren, ...(isRichTableEnabled && { id: "table-0-0" }) },
            { type: "p", children: [{ text: "" }] },
        ],
    };
};

export const tableWithMultipleColspansAndRowspans_json =
    '[{"type":"p","children":[{"text":"hey complicated tabel"}]},{"type":"table","children":[{"type":"tbody","children":[{"type":"tr","children":[{"type":"td","attributes":{"rowspan":"5","colspan":"1"},"children":[{"text":"header 1"}]},{"type":"td","children":[{"text":"header 2"}]},{"type":"td","children":[{"text":"header 3"}]},{"type":"td","children":[{"text":"header 4"}]},{"type":"td","children":[{"text":"header 5"}]},{"type":"td","children":[{"text":"header 6"}]},{"type":"td","children":[{"text":"header 7"}]},{"type":"td","children":[{"text":"header 8"}]},{"type":"td","children":[{"text":"header 9"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"2"}]},{"type":"td","children":[{"text":"3"}]},{"type":"td","children":[{"text":"4"}]},{"type":"td","children":[{"text":"5"}]},{"type":"td","children":[{"text":"6"}]},{"type":"td","children":[{"text":"7"}]},{"type":"td","children":[{"text":"8"}]},{"type":"td","children":[{"text":"9"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"11"}]},{"type":"td","children":[{"text":"12"}]},{"type":"td","children":[{"text":"13"}]},{"type":"td","children":[{"text":"14"}]},{"type":"td","attributes":{"rowspan":"1","colspan":"4"},"children":[{"text":"15"}]}]},{"type":"tr","children":[{"type":"td","attributes":{"rowspan":"5","colspan":"1"},"children":[{"text":"20"}]},{"type":"td","attributes":{"rowspan":"3","colspan":"1"},"children":[{"text":"21"}]},{"type":"td","children":[{"text":"22"}]},{"type":"td","children":[{"text":"23"}]},{"type":"td","children":[{"text":"24"}]},{"type":"td","children":[{"text":"25"}]},{"type":"td","children":[{"text":"26"}]},{"type":"td","children":[{"text":"27"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"31"}]},{"type":"td","attributes":{"rowspan":"4","colspan":"1"},"children":[{"text":"32"}]},{"type":"td","attributes":{"rowspan":"3","colspan":"4"},"children":[{"text":"33"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"37"}]},{"type":"td","children":[{"text":"40"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"46"}]},{"type":"td","children":[{"text":"48"}]},{"type":"td","children":[{"text":"49"}]}]},{"type":"tr","children":[{"type":"td","children":[{"text":"55"}]},{"type":"td","children":[{"text":"57"}]},{"type":"td","children":[{"text":"58"}]},{"type":"td","children":[{"text":"60"}]},{"type":"td","children":[{"text":"61"}]},{"type":"td","children":[{"text":"62"}]},{"type":"td","children":[{"text":"63"}]}]}]}]},{"type":"p","children":[{"text":""}]}]';
