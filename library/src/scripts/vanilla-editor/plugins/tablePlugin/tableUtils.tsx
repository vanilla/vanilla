/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";
import {
    MyEditor,
    MyTableElement,
    MyTableHeaderType,
    MyTableRowElement,
    MyTableCellElement,
    MyValue,
} from "@library/vanilla-editor/typescript";
import { getAboveNode, getBlockAbove, setNodes, TElement, EElementOrText } from "@udecode/plate-common";
import {
    ELEMENT_TD,
    ELEMENT_TH,
    ELEMENT_TR,
    ELEMENT_TABLE,
    getTableAbove,
    getEmptyCellNode,
} from "@udecode/plate-table";
import { withoutNormalizing, nodes } from "slate";

export const getCellPosition = (editor) => {
    const cellEntry = getAboveNode(editor, {
        match: (node: TElement) => node.type === ELEMENT_TD || node.type === ELEMENT_TH,
    });
    const cellNode = cellEntry?.[0];

    const tableEntry = getTableAbove(editor);

    if (cellNode) {
        const rowEntry = getBlockAbove(editor, {
            match: { type: ELEMENT_TR },
            at: cellEntry?.[1],
        });

        if (tableEntry && rowEntry) {
            const [rowNode] = rowEntry;
            const [tableNode] = tableEntry;

            return {
                row: (tableNode as TElement).children.indexOf(rowNode as TElement),
                col: (rowNode as TElement).children.indexOf(cellNode as TElement),
            };
        }
    }
    return { row: undefined, col: undefined };
};

/**
 * Convert tabel headers to `top`, `left` or `both`.
 */
export const convertHeaders = (editor: MyEditor, headerType: MyTableHeaderType) => {
    const tableEntry = getTableAbove(editor);
    if (!tableEntry) return;

    const tableNode = tableEntry[0] as MyTableElement;
    const tablePath = tableEntry[1];
    const rows = tableNode.children as MyTableRowElement[];

    if (rows.length > 0) {
        withoutNormalizing(editor, () => {
            switch (headerType) {
                case "left":
                    rows.forEach((row, rowIndex) => {
                        row.children.forEach((_, colIndex) => {
                            setNodes(
                                editor,
                                { type: colIndex === 0 ? ELEMENT_TH : ELEMENT_TD },
                                { at: [...tablePath, rowIndex, colIndex] },
                            );
                        });
                    });
                    break;
                case "top":
                    rows.forEach((row, rowIndex) => {
                        row.children.forEach((cell, colIndex) => {
                            if (rowIndex === 0) {
                                setNodes(editor, { type: ELEMENT_TH }, { at: [...tablePath, rowIndex, colIndex] });
                            } else if (cell.type === ELEMENT_TH) {
                                setNodes(editor, { type: ELEMENT_TD }, { at: [...tablePath, rowIndex, colIndex] });
                            }
                        });
                    });
                    break;
                case "both":
                    rows.forEach((row, rowIndex) => {
                        row.children.forEach((cell, colIndex) => {
                            // first cell should be <td> though
                            if (rowIndex === 0 && colIndex === 0) {
                                if (cell.type !== ELEMENT_TD) {
                                    setNodes(editor, { type: ELEMENT_TD }, { at: [...tablePath, rowIndex, colIndex] });
                                }
                            }
                            // first row & first column to <th>
                            else if (rowIndex === 0 || colIndex === 0) {
                                if (cell.type !== ELEMENT_TH) {
                                    setNodes(editor, { type: ELEMENT_TH }, { at: [...tablePath, rowIndex, colIndex] });
                                }
                            }
                            // all other <th> to <td>
                            else if (cell.type === ELEMENT_TH) {
                                setNodes(editor, { type: ELEMENT_TD }, { at: [...tablePath, rowIndex, colIndex] });
                            }
                        });
                    });
                    break;
            }
        });
    }
};

const processTableNodes = (
    nodes: Array<EElementOrText<MyValue>>,
    processSection: (section: EElementOrText<MyValue>) => EElementOrText<MyValue>,
): Array<EElementOrText<MyValue>> => {
    const processNode = (node: EElementOrText<MyValue>): EElementOrText<MyValue> => {
        if ((node as TElement).type === ELEMENT_TABLE) {
            return {
                ...node,
                children: ((node as TElement).children || []).map(processSection),
            };
        }

        if ((node as TElement).children) {
            return {
                ...node,
                children: (node as TElement).children.map(processNode),
            };
        }

        return node;
    };

    return nodes.map(processNode);
};

const cleanTableColspan = (editor: MyEditor, nodes: Array<EElementOrText<MyValue>>): Array<EElementOrText<MyValue>> => {
    const processSection = (sectionNode: TElement): TElement => {
        const rows = (sectionNode.children || []).filter((node) => node.type === ELEMENT_TR);

        const normalizedRows = (rows as MyTableRowElement[]).map((row) => {
            const newRowChildren: MyTableCellElement[] = [];

            for (const cell of row.children || []) {
                if (typeof cell !== "object" || !("type" in cell)) continue;
                if (cell.type !== ELEMENT_TD && cell.type !== ELEMENT_TH) {
                    newRowChildren.push(cell);
                    continue;
                }

                const colspan = cell.attributes?.colspan ? parseInt(cell.attributes.colspan) : 1;
                const cleanCell = { ...cell };
                if (colspan > 1) {
                    delete cleanCell.attributes?.colspan;
                }
                newRowChildren.push(cleanCell);

                const emptyTableCell = getEmptyCellNode(editor, {
                    header: cell.type === ELEMENT_TH,
                    newCellChildren: [{ text: " " }],
                }) as MyTableCellElement;

                for (let i = 1; i < colspan; i++) {
                    newRowChildren.push({ ...emptyTableCell });
                }
            }

            return {
                ...row,
                children: newRowChildren,
            };
        });

        return {
            ...sectionNode,
            children: normalizedRows,
        };
    };

    return processTableNodes(nodes, processSection);
};

const cleanTableRowspan = (editor: MyEditor, nodes: Array<EElementOrText<MyValue>>): Array<EElementOrText<MyValue>> => {
    const processSection = (sectionNode: TElement): TElement => {
        const rows = (sectionNode.children || []).filter((node) => node.type === ELEMENT_TR) as MyTableRowElement[];

        const rowspanMap: Record<number, Record<number, MyTableCellElement>> = {};

        const normalizedRows = rows.map((row, rowIndex) => {
            const newRowChildren: MyTableCellElement[] = [];
            const rowCells = (row.children || []).filter(
                (child) => child.type === ELEMENT_TD || child.type === ELEMENT_TH,
            );

            let colIndex = 0;
            let cellPointer = 0;

            while (cellPointer < rowCells.length || rowspanMap[rowIndex]?.[colIndex]) {
                if (rowspanMap[rowIndex]?.[colIndex]) {
                    newRowChildren.push(rowspanMap[rowIndex][colIndex]);
                    colIndex++;
                    continue;
                }

                const cell = rowCells[cellPointer];
                cellPointer++;

                const rowspan = cell.attributes?.rowspan ? parseInt(cell.attributes.rowspan) : 1;
                const colspan = cell.attributes?.colspan ? parseInt(cell.attributes.colspan) : 1;

                const cleanCell = { ...cell };
                if (rowspan > 1) {
                    delete cleanCell.attributes?.rowspan;
                }
                newRowChildren.push(cleanCell);

                if (rowspan > 1) {
                    for (let i = 1; i < rowspan; i++) {
                        const targetRow = rowIndex + i;
                        if (!rowspanMap[targetRow]) rowspanMap[targetRow] = {};

                        const emptyTableCell = getEmptyCellNode(editor, {
                            header: cell.type === "th",
                            newCellChildren: [{ text: " " }],
                        }) as MyTableCellElement;

                        for (let j = 0; j < colspan; j++) {
                            rowspanMap[targetRow][colIndex + j] = { ...emptyTableCell };
                        }
                    }
                }

                colIndex += colspan;
            }

            return {
                ...row,
                children: newRowChildren,
            };
        });

        return {
            ...sectionNode,
            children: normalizedRows,
        };
    };

    return processTableNodes(nodes, processSection);
};

export const cleanTableRowspanColspan = (
    editor: MyEditor,
    nodes: Array<EElementOrText<MyValue>> | MyValue,
): Array<EElementOrText<MyValue>> => {
    nodes = cleanTableRowspan(editor, nodes);
    nodes = cleanTableColspan(editor, nodes);
    return nodes;
};

export const needsRowspanColspanCleaning = (nodes: Array<EElementOrText<MyValue>> | MyValue): boolean => {
    const checkNode = (node: EElementOrText<MyValue>): boolean => {
        if (typeof node !== "object" || !("type" in node)) return false;

        const { type, attributes, children } = node;

        if ((type === ELEMENT_TD || type === ELEMENT_TH) && attributes) {
            const rowspan = parseInt((attributes as any).rowspan);
            const colspan = parseInt((attributes as any).colspan);

            if (rowspan > 1 || colspan > 1) return true;
        }

        if (Array.isArray(children)) {
            return children.some(checkNode);
        }

        return false;
    };

    return Array.isArray(nodes) ? nodes.some(checkNode) : false;
};

export const needsInitialNormalizationForTables = (editor: MyEditor, initValue?: MyValue) => {
    const isRichTableEnabled = getMeta("featureFlags.RichTable.Enabled", false);

    if (isRichTableEnabled && initValue) {
        const tempEditor = { ...editor, children: initValue } as MyEditor;
        const tables = [
            ...nodes(tempEditor, {
                at: [],
                match: (node) => (node as any).type === ELEMENT_TABLE,
            }),
        ];
        if (tables.length > 0 && tables.some((table) => !table["id"])) {
            return true;
        }
    }
    return false;
};
