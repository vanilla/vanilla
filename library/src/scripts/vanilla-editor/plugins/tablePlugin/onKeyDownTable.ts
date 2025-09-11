/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    Hotkeys,
    KeyboardHandlerReturnType,
    PlateEditor,
    PluginOptions,
    select,
    selectEditor,
    TElement,
    Value,
    WithPlatePlugin,
} from "@udecode/plate-common";
import {
    getNextTableCell,
    getPreviousTableCell,
    getTableAbove,
    getTableEntries,
    insertTableRow,
} from "@udecode/plate-table";

export const onKeyDownTable =
    <P = PluginOptions, V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(
        editor: E,
        { type }: WithPlatePlugin<P, V, E>,
    ): KeyboardHandlerReturnType =>
    (e) => {
        if (e.defaultPrevented) return;

        const isEscape = e.key === "Escape";
        const isTab = Hotkeys.isTab(editor, e);
        const isUntab = Hotkeys.isUntab(editor, e);
        if (isTab || isUntab || isEscape) {
            const entries = getTableEntries(editor);
            if (!entries) return;

            if (isEscape) {
                // Exit the table by moving cursor after the table
                const tableEntry = getTableAbove(editor);
                if (tableEntry) {
                    const [, tablePath] = tableEntry;
                    const nextPath = [tablePath[0] + 1];
                    select(editor, nextPath);
                }
                return;
            }

            const { row, cell } = entries;
            const [, cellPath] = cell;

            if (isUntab) {
                const tableEntry = getTableAbove(editor);
                const isFirstCellInRow = cellPath[2] === 0;
                const isFirstCellInTable = cellPath[1] === 0 && cellPath[2] === 0;

                if (isFirstCellInTable) {
                    // We're at the very first cell, exit the table by moving cursor before the table
                    if (tableEntry) {
                        const [, tablePath] = tableEntry;
                        select(editor, [tablePath[0] - 1]);
                    }
                } else if (!isFirstCellInRow) {
                    const previousCell = getPreviousTableCell(editor, cell, cellPath, row);
                    if (previousCell) {
                        const [, previousCellPath] = previousCell;
                        select(editor, previousCellPath);
                    }
                } else {
                    // We're at the first cell of the row, for some reason plate's getPreviousTableCell is throwing an error in this case
                    // so we need to do this manually here
                    const currentRowIndex = cellPath[1];
                    if (currentRowIndex > 0) {
                        const previousRowIndex = currentRowIndex - 1;
                        if (tableEntry) {
                            const [tableNode] = tableEntry;
                            const previousRow = tableNode.children[previousRowIndex] as TElement;
                            if (previousRow && previousRow.children) {
                                const lastCellIndex = previousRow.children.length - 1;
                                const lastCellPath = [cellPath[0], previousRowIndex, lastCellIndex];
                                select(editor, lastCellPath);
                            }
                        }
                    }
                }
            } else if (isTab) {
                // move right with tab
                const nextCell = getNextTableCell(editor, cell, cellPath, row);
                if (nextCell) {
                    const [, nextCellPath] = nextCell;
                    select(editor, nextCellPath);
                } else {
                    // we are in the last cell, let's add a new row
                    const currentRowPath = cellPath && cellPath.slice(0, 2);
                    insertTableRow(editor, {
                        fromRow: currentRowPath,
                    });
                    const nextCell = getNextTableCell(editor, cell, cellPath, row);
                    // select after the new row
                    if (nextCell) {
                        const [, nextCellPath] = nextCell;
                        select(editor, nextCellPath);
                    }
                }
            }

            e.preventDefault();
            e.stopPropagation();
        }
    };
