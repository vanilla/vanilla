/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import {
    ELEMENT_TBODY,
    ELEMENT_TFOOT,
    ELEMENT_THEAD,
} from "@library/vanilla-editor/plugins/tablePlugin/createTablePlugin";
import { MyEditor } from "@library/vanilla-editor/typescript";
import {
    PlateEditor,
    TElement,
    Value,
    findNode,
    getBlockAbove,
    getParentNode,
    getPluginOptions,
    getPluginType,
    getTEditor,
    isElement,
    isText,
    setNodes,
    unwrapNodes,
    withoutNormalizing,
    wrapNodeChildren,
    getChildren,
    removeNodes,
} from "@udecode/plate-common";
import {
    ELEMENT_TABLE,
    ELEMENT_TD,
    ELEMENT_TH,
    ELEMENT_TR,
    getCellTypes,
    TablePlugin,
    TTableCellElement,
    TTableElement,
} from "@udecode/plate-table";
import { Path } from "slate";

export const withNormalizeRichTable = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(
    editor: E,
) => {
    const { normalizeNode } = editor;
    const myEditor = getTEditor<V>(editor);

    const tableType = getPluginType(editor, ELEMENT_TABLE);
    const tbodyType = getPluginType(editor, ELEMENT_TBODY);
    const theadType = getPluginType(editor, ELEMENT_THEAD);
    const tfootType = getPluginType(editor, ELEMENT_TFOOT);
    const trType = getPluginType(editor, ELEMENT_TR);
    const thType = getPluginType(editor, ELEMENT_TH);
    const tdType = getPluginType(editor, ELEMENT_TD);

    const { initialTableWidth } = getPluginOptions<TablePlugin>(editor as any, ELEMENT_TABLE);

    myEditor.normalizeNode = ([node, path]) => {
        if (isElement(node)) {
            if (node.type === tableType) {
                const tableEntry = getBlockAbove(editor, {
                    at: path,
                    match: { type: tableType },
                });

                if (tableEntry) {
                    unwrapNodes(editor, { at: path });
                    return;
                }

                // our tables should always have an id
                // if we are setting it here, most probably it's coming from a copy-paste, or we are editing a table added by old system
                if (!node.id) {
                    withoutNormalizing(editor, () => {
                        setNodes(editor as MyEditor, { id: `${uniqueIDFromPrefix("table")}-${path}` }, { at: path });
                    });

                    // let's do default top headers if no headers found
                    const foundHeader = findNode(editor, {
                        at: path,
                        match: {
                            type: thType,
                        },
                    });
                    if (!foundHeader) {
                        const firstRow = findNode(editor, {
                            at: path,
                            match: {
                                type: trType,
                            },
                        });
                        const firstRowNode = firstRow?.[0] as TElement;
                        const firstRowPath = firstRow?.[1] as Path;
                        if (getParentNode(editor, firstRowPath)?.[0].type === tableType) {
                            withoutNormalizing(editor, () => {
                                firstRowNode.children.forEach((cell, cellIndex) => {
                                    if (cell.type === tdType) {
                                        setNodes<TTableCellElement>(
                                            editor,
                                            { type: thType },
                                            { at: [...firstRowPath, cellIndex] },
                                        );
                                    }
                                });
                            });
                        }
                    }
                    // and some cleanup in case table children are invalid elements
                    const tableChildren = getChildren([node, path]);
                    const validChildrenTypes = [thType, tdType, trType, tbodyType, theadType, tfootType];
                    tableChildren.forEach((child) => {
                        const childNode = child[0] as TElement;
                        const childPath = child[1] as Path;

                        if (!validChildrenTypes.includes(childNode["type"])) {
                            withoutNormalizing(editor, () => {
                                removeNodes(editor, { at: childPath });
                            });
                        }
                    });
                }

                if (initialTableWidth) {
                    const tableNode = node as TTableElement;
                    const colCount = (
                        tableNode.children.find((child) => child.type === ELEMENT_TR)?.children as
                            | TElement[]
                            | undefined
                    )?.length;
                    if (colCount) {
                        const colSizes: number[] = [];

                        if (!tableNode.colSizes) {
                            for (let i = 0; i < colCount; i++) {
                                colSizes.push(initialTableWidth / colCount);
                            }
                        } else if (tableNode.colSizes.some((size) => !size)) {
                            tableNode.colSizes.forEach((colSize) => {
                                colSizes.push(colSize || initialTableWidth / colCount);
                            });
                        }

                        if (colSizes.length) {
                            setNodes<TTableElement>(editor, { colSizes }, { at: path });
                            return;
                        }
                    }
                }
            }

            if (node.type === theadType || node.type === tbodyType || node.type === tfootType) {
                const parentEntry = getParentNode(editor, path);
                // we don't want `thead` or `tfoot` in the table as per plate examples
                // we don't want `tbody` in the table as well, as tbody wrapper is in the table renderer
                if (parentEntry?.[0].type === tableType) {
                    unwrapNodes(editor, {
                        at: path,
                    });
                    return;
                }
            }

            if (getCellTypes<Value & V>(editor as PlateEditor<Value & V>).includes(node.type)) {
                const { children } = node;

                if (isText(children[0])) {
                    wrapNodeChildren<TElement>(editor, editor.blockFactory({}, path), {
                        at: path,
                    });

                    return;
                }
            }
        }

        return normalizeNode([node, path]);
    };

    return editor;
};
