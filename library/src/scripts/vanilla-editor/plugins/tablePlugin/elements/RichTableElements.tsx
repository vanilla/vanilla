/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { userContentClasses } from "@library/content/UserContent.styles";
import {
    findNodePath,
    PlateRenderElementProps,
    setNodes,
    toDOMNode,
    withoutNormalizing,
    select,
    focusEditor,
} from "@udecode/plate-common";
import { ReactEditor } from "slate-react";
import {
    getTableAbove,
    TableCellElement as PlateTableCellElement,
    TableElement as PlateTableElement,
    TableRowElement as PlateTableRowElement,
    TTableElement,
    useTableCellElementState,
    useTableElementState,
} from "@udecode/plate-table";
import { cx } from "@emotion/css";
import { richTableElementsClasses } from "@library/vanilla-editor/plugins/tablePlugin/elements/RichTableElements.classes";
import { useVanillaEditorTable } from "@library/vanilla-editor/plugins/tablePlugin/VanillaEditorTableContext";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import ReactDOM from "react-dom";
import debounce from "lodash-es/debounce";
import { useLastValue } from "@vanilla/react-utils";
import { getCellPosition } from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { MyEditor, MyTableCellElement, MyTableElement, MyTableRowElement } from "@library/vanilla-editor/typescript";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Path } from "slate";
import { MyTableHighlightArea, MyTableMeasures } from "@library/vanilla-editor/typescript";
import { t } from "@vanilla/i18n";

export const RichTableElement = (props: PlateRenderElementProps<any, MyTableElement>) => {
    const classes = userContentClasses();

    const { children, ...rootProps } = props;
    const { editor, element } = rootProps;

    const { colSizes, minColumnWidth, marginLeft, isSelectingCell } = useTableElementState();

    const tableRef = useRef<HTMLTableElement>(null);
    const [liveMessage, setLiveMessage] = useState<string>("");
    const [hasAnnouncedEntry, setHasAnnouncedEntry] = useState(false);

    const tableID = element.id;

    const { tablesByID, updateTableState } = useVanillaEditorTable();

    const { tableHighlightedArea, rowSizesByIndex, contentAlignment } = tablesByID[tableID] ?? {};

    // sometimes colsize can be smaller than minColumnWidth (which is not correct), so we need to manually adjust it
    // also let's take element.colSizes if it's available as looks like the one from state updates a bit later when we insert a new column
    const actualColSizes = [
        ...(element.colSizes ?? colSizes).map((width) => (width < minColumnWidth ? minColumnWidth : width)),
        ...(element.colSizes ? ["100%"] : []),
    ];

    const tableWidth = actualColSizes.reduce<number>((acc, width, index) => {
        // last one is the empty placeholder  - `100%`
        if (index < colSizes.length - 1) {
            return acc + (typeof width === "string" ? 0 : width);
        }
        return acc;
    }, 0);

    const previousTableID = useLastValue(tableID);
    const previousTableWidth = useLastValue(tableWidth);
    const lastMarginLeft = useLastValue(marginLeft);

    const rowSizesLength = Object.keys(rowSizesByIndex ?? {}).length;
    const shouldResetRowSizes = !rowSizesLength || element.children.length !== rowSizesLength;

    // we should set initial content alignment if there is one (normally when first editing a table)
    useEffect(() => {
        if (!contentAlignment && element.contentAlignment) {
            updateTableState(tableID, {
                contentAlignment: element.contentAlignment,
            });
        }
    }, [tableID, element.contentAlignment, contentAlignment]);

    useEffect(() => {
        if (shouldResetRowSizes) {
            const initialRowSizesByIndex = Object.fromEntries(
                element.children.map((row, index) => {
                    // element.size has some mismatch with actual height and even can be negative when we reducing row size,
                    // so let's rely on rowRect.height rather
                    const rowNode = toDOMNode(editor, row);
                    const rowRect = rowNode?.getBoundingClientRect();
                    return [index, rowRect?.height ?? (element.size as number)];
                }),
            );
            updateTableState(tableID, { rowSizesByIndex: initialRowSizesByIndex });
        }
        // clean up if table is gone
        return () => {
            rowSizesLength && updateTableState(tableID, { rowSizesByIndex: {} });
        };
    }, [shouldResetRowSizes]);

    const debouncedTableWidth = useCallback(
        debounce((measures: MyTableMeasures) => {
            updateTableState(tableID, { tableMeasures: measures });
        }, 250),
        [],
    );

    useEffect(() => {
        if (tableID !== previousTableID || tableWidth !== previousTableWidth || marginLeft !== lastMarginLeft) {
            debouncedTableWidth({
                actualWidth: tableWidth,
                marginLeft,
            });
        }
    }, [tableID, tableWidth, marginLeft]);

    useEffect(() => {
        updateTableState(tableID, { multipleCellsSelected: isSelectingCell });
    }, [isSelectingCell]);

    // AIDEV-NOTE: Live message for table entry - announce once when entering table
    useEffect(() => {
        const isInTable = getTableAbove(editor, { at: editor.selection?.anchor.path });
        if (isInTable && !hasAnnouncedEntry) {
            setLiveMessage(t("You are currently in a table. Use Tab to navigate cells, Esc to exit editing"));
            setHasAnnouncedEntry(true);
        } else if (!isInTable && hasAnnouncedEntry) {
            // Announce table exit
            setLiveMessage(t("You have left the table"));
            setHasAnnouncedEntry(false);
        }
        // Clear message after announcement
        setTimeout(() => {
            setLiveMessage("");
        }, 1000);
    }, [editor.selection, hasAnnouncedEntry]);

    return (
        <>
            <PlateTableElement.Wrapper
                style={{ paddingLeft: marginLeft }}
                className={cx(classes.tableWrapper, richTableElementsClasses().tableWrapper, "customized")}
                tabIndex={-1}
            >
                <PlateTableElement.Root
                    {...rootProps}
                    className={cx(richTableElementsClasses().table, rootProps.className)}
                    ref={tableRef}
                    id={rootProps?.element?.id as string}
                >
                    <PlateTableElement.ColGroup>
                        {actualColSizes.map((width, index) => (
                            <PlateTableElement.Col
                                key={index}
                                style={{
                                    minWidth: minColumnWidth,
                                    width: width || undefined,
                                }}
                            />
                        ))}
                    </PlateTableElement.ColGroup>

                    <PlateTableElement.TBody>{children}</PlateTableElement.TBody>
                </PlateTableElement.Root>
                <TableHighlightOverlay
                    editor={editor}
                    highlightArea={tableHighlightedArea}
                    tableMeasures={{ actualWidth: tableWidth, marginLeft }}
                    colSizes={actualColSizes as number[]}
                    rowSizesByIndex={rowSizesByIndex}
                    isSelectingCell={isSelectingCell}
                />
            </PlateTableElement.Wrapper>

            {/* Live announcements for table navigation - rendered via portal to prevent extra arrow key when navigating from table or inside table */}
            {ReactDOM.createPortal(
                <div
                    aria-live="polite"
                    aria-atomic="true"
                    style={{
                        position: "absolute",
                        left: "-10000px",
                        width: "1px",
                        height: "1px",
                        overflow: "hidden",
                    }}
                >
                    {liveMessage}
                </div>,
                document.body,
            )}
        </>
    );
};

export const RichTableRowElement = (props: PlateRenderElementProps<any, MyTableRowElement>) => {
    const { children, ...rootProps } = props;

    const { editor, element } = rootProps;

    const currentRowPath = findNodePath(editor, element) as Path;
    const tableEntry = getTableAbove(editor, { at: currentRowPath });
    const tableNode = tableEntry?.[0] as MyTableElement;

    const tableID = tableNode?.id;

    const { tablesByID, updateTableState } = useVanillaEditorTable();

    const rowSizesByIndex = tablesByID[tableID]?.rowSizesByIndex;

    const currentRowIndex = currentRowPath?.[currentRowPath.length - 1];

    const rowNode = toDOMNode(editor, element);
    const rowRect = rowNode?.getBoundingClientRect();

    const rowSizesLength = Object.keys(rowSizesByIndex ?? {}).length;
    const isCurrentRowSizeChanged =
        currentRowIndex !== undefined && rowSizesByIndex?.[currentRowIndex] !== rowRect?.height;

    useEffect(() => {
        if (isCurrentRowSizeChanged && rowSizesLength) {
            const rowSizes = {
                ...rowSizesByIndex,
                [currentRowIndex]: rowRect?.height ?? (element.size as number),
            };
            updateTableState(tableID, {
                rowSizesByIndex: rowSizes,
            });
            // update so the BE can get the new row size for HTML
            withoutNormalizing(editor, () => {
                setNodes<TTableElement>(editor, { actualHeight: rowSizes[currentRowIndex] }, { at: currentRowPath });
            });
        }
    }, [rowRect?.height, rowSizesLength]);

    return (
        <PlateTableRowElement.Root {...rootProps} className={cx(richTableElementsClasses().row, rootProps.className)}>
            {children}
        </PlateTableRowElement.Root>
    );
};

export const RichTableHeaderCellElement = (props: PlateRenderElementProps<any, MyTableCellElement>) => {
    return <RichTableCellElement {...props} isHeader={true} />;
};

/**
 * AIDEV-NOTE: Table Cell Selection Fix
 *
 * Ensures single-click cursor placement works correctly in table cells with resizable elements.
 * Without this, users would need to double-click to position the cursor in table cells.
 *
 * Uses onClick (not onMouseDown) to ensure DOM state is fully updated before setting selection.
 * Converts click coordinates to precise text positions for accurate cursor placement.
 */
export const RichTableCellElement = (
    props: PlateRenderElementProps<any, MyTableCellElement> & { isHeader?: boolean },
) => {
    const { children, isHeader, ...rootProps } = props;

    const { editor, element } = rootProps;

    const currentCellPath = findNodePath(editor, element) as Path;
    const tableEntry = getTableAbove(editor, { at: currentCellPath });
    const tableNode = tableEntry?.[0] as MyTableElement;

    const tableID = tableNode?.id;

    const { colIndex, rowIndex, readOnly, borders, rowSize } = useTableCellElementState();

    const { tablesByID } = useVanillaEditorTable();
    const contentAlignment = tablesByID[tableID]?.contentAlignment;

    const alignment = useMemo(() => {
        const currentColumnPath = currentCellPath && currentCellPath.slice(0, 3);
        const currentRowPath = currentCellPath && currentCellPath.slice(0, 2);
        const currentColumnIndex = currentColumnPath?.[currentColumnPath.length - 1] ?? -1;
        const currentRowIndex = currentRowPath?.[currentRowPath.length - 1] ?? -1;
        const columnAlignment = contentAlignment?.columns?.[currentColumnIndex];
        const rowAlignment = contentAlignment?.rows?.[currentRowIndex];
        const hasColumnAndRowAlignmentApplied = columnAlignment && rowAlignment;
        if (hasColumnAndRowAlignmentApplied) {
            return columnAlignment?.appliedTimestamp > rowAlignment?.appliedTimestamp
                ? columnAlignment?.alignment
                : rowAlignment?.alignment;
        } else if (columnAlignment) {
            return columnAlignment?.alignment;
        } else if (rowAlignment) {
            return rowAlignment?.alignment;
        }
    }, [contentAlignment, currentCellPath]);

    // this bit is for BE to assign alignment per cell
    useEffect(() => {
        if (alignment && alignment !== "start" && alignment !== element.alignment) {
            withoutNormalizing(editor, () => {
                setNodes<TTableElement>(
                    editor,
                    { attributes: { style: `text-align:${alignment}` } },
                    { at: currentCellPath },
                );
            });
        }
    }, [alignment]);

    return (
        <PlateTableCellElement.Root
            asAlias={isHeader ? "th" : "td"}
            {...rootProps}
            className={cx(richTableElementsClasses(borders).cell, rootProps.className)}
            id={rootProps?.element?.id as string | undefined}
            onClick={(e) => {
                // AIDEV-NOTE: Ensures precise cursor placement in table cells
                if (currentCellPath && editor) {
                    try {
                        // Convert mouse coordinates to exact text position
                        const range = ReactEditor.findEventRange(editor, e.nativeEvent || e);

                        if (range) {
                            // Place cursor at exact click position
                            select(editor, range);
                        } else {
                            // Fallback: place cursor at start of cell content
                            select(editor, [...currentCellPath, 0, 0]);
                        }

                        // Ensure editor maintains focus
                        focusEditor(editor);
                    } catch (error) {
                        // Fallback selection to prevent broken state
                        try {
                            select(editor, [...currentCellPath, 0, 0]);
                            focusEditor(editor);
                        } catch {
                            // Silently fail if both attempts fail
                        }
                    }
                }
            }}
        >
            <PlateTableCellElement.Content
                className={richTableElementsClasses().cellContent}
                style={{
                    minHeight: rowSize,
                    textAlign: alignment,
                }}
            >
                {children}
            </PlateTableCellElement.Content>

            <PlateTableCellElement.ResizableWrapper
                className={cx(richTableElementsClasses().cellResizableWrapper, "group")}
            >
                <PlateTableCellElement.Resizable colIndex={colIndex} rowIndex={rowIndex} readOnly={readOnly} />
            </PlateTableCellElement.ResizableWrapper>
        </PlateTableCellElement.Root>
    );
};

interface TableOverlayProps {
    editor: MyEditor;
    tableMeasures: MyTableMeasures;
    colSizes: number[];
    highlightArea?: MyTableHighlightArea;
    rowSizesByIndex?: Record<number, number>;
    isSelectingCell?: boolean;
}

const TableHighlightOverlay = (props: TableOverlayProps) => {
    const { editor, tableMeasures, highlightArea, colSizes, rowSizesByIndex, isSelectingCell } = props;
    const [overlayStyle, setOverlayStyle] = useState({});

    const initialStyle = {
        position: "absolute",
        top: 0,
        bottom: 0,
        border: `2px solid ${ColorsUtils.colorOut(globalVariables().mainColors.primary)}`,
        pointerEvents: "none",
        zIndex: 1,
    };
    const { row: rowIndex, col: colIndex } = getCellPosition(editor);

    useEffect(() => {
        // can be 0 that's why we need to check for undefined
        if (colIndex !== undefined && rowIndex !== undefined) {
            const tableHeight = Object.values(rowSizesByIndex ?? {}).reduce((acc, rowHeight) => acc + rowHeight, 0);
            let highlight = {};

            switch (highlightArea) {
                case "table":
                    highlight = {
                        left: tableMeasures.marginLeft ?? 0,
                        width: tableMeasures.actualWidth + 1,
                        height: tableHeight + 1,
                    };
                    break;
                case "column":
                    const leftPosition =
                        (tableMeasures.marginLeft ?? 0) +
                        colSizes.slice(0, colIndex).reduce((acc, width) => acc + width, 0);
                    highlight = {
                        top: 1,
                        left: colIndex === 0 ? leftPosition : leftPosition - 1,
                        width: colSizes[colIndex] + 1,
                        height: tableHeight,
                    };
                    break;
                case "row":
                    const topPosition = Object.values(rowSizesByIndex ?? {})
                        .slice(0, rowIndex)
                        .reduce((acc, height) => acc + height, 0);
                    highlight = {
                        left: tableMeasures?.marginLeft ?? 0,
                        top: topPosition,
                        width: tableMeasures.actualWidth,
                        height: (rowSizesByIndex?.[rowIndex] ?? 0) + 2,
                    };
                    break;
            }

            setOverlayStyle({
                ...initialStyle,
                ...highlight,
            });
        }
    }, [editor.selection, highlightArea, rowSizesByIndex, colSizes]);

    if (!highlightArea || isSelectingCell) {
        return null;
    }

    return <div className="highlight-overlay" style={overlayStyle} />;
};
