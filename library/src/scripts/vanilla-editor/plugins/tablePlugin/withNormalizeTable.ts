/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ELEMENT_TABLE,
    ELEMENT_TH,
    PlateEditor,
    Value,
    getTEditor,
    isElement,
    getPluginType,
    getBlockAbove,
    unwrapNodes,
    getChildren,
    wrapNodeChildren,
    EElement,
    setElements,
    getParentNode,
    getCellTypes,
    isText,
    TElement,
    insertElements,
    withoutNormalizing,
    removeNodes,
} from "@udecode/plate-headless";
import { ELEMENT_TBODY, ELEMENT_THEAD } from "@library/vanilla-editor/plugins/tablePlugin/createTablePlugin";

export const withNormalizeTable = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { normalizeNode } = editor;
    const myEditor = getTEditor<V>(editor);

    const tableType = getPluginType(editor, ELEMENT_TABLE);
    const tbodyType = getPluginType(editor, ELEMENT_TBODY);
    const theadType = getPluginType(editor, ELEMENT_THEAD);
    const thType = getPluginType(editor, ELEMENT_TH);

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

                // Table does not have a TBODY element, wrap rows in one
                const tbodyExists = getChildren([node, path]).filter(([child]) => child.type === tbodyType).length > 0;
                if (!tbodyExists) {
                    const tbodyEl = { type: tbodyType } as EElement<V>;
                    wrapNodeChildren(editor, tbodyEl, { at: path });
                }
            }

            if (node.type === theadType) {
                // ensure that all cells in the `thead` are `th` and not `td`
                getChildren([node, path]).forEach((row) => {
                    getChildren(row).forEach(([cellNode, cellPath]) => {
                        if (cellNode.type !== thType) {
                            setElements(editor, { type: thType }, { at: cellPath });
                        }
                    });
                });
            }

            if (node.type === tbodyType) {
                const parentEntry = getParentNode(editor, path);

                if (parentEntry) {
                    // If there is not a `thead` element, then add a `thead` element and move the first row to the `thead` element
                    const theadExists =
                        getChildren(parentEntry).filter(([child]) => child.type === theadType).length > 0;
                    if (!theadExists) {
                        const [headers] = getChildren([node, path]);
                        const [headersNode, headersPath] = headers;
                        withoutNormalizing(editor, () => {
                            // remove first row from the body
                            removeNodes(editor, { at: headersPath });
                            // add a `thead` element with the first row
                            insertElements(editor, { type: theadType, children: [headersNode] }, { at: path });
                        });
                    }
                }
            }

            if (getCellTypes(editor).includes(node.type)) {
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
