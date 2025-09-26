/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { t } from "@library/utility/appUtils";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { useMyEditorState } from "@library/vanilla-editor/getMyEditor";
import {
    findNodePath,
    focusEditor,
    getEndPoint,
    getStartPoint,
    removeNodes,
    setNodes,
    withoutNormalizing,
} from "@udecode/plate-common";
import { getRangeBoundingClientRect } from "@udecode/plate-floating";
import { Icon } from "@vanilla/icons";
import { forwardRef, MutableRefObject, useEffect, useMemo, useState } from "react";
import { MenuBarSubMenuItemGroup } from "@library/MenuBar/MenuBarSubMenuItemGroup";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import {
    deleteColumn,
    deleteRow,
    deleteTable,
    getTableAbove,
    insertTableColumn,
    insertTableRow,
    TTableElement,
} from "@udecode/plate-table";
import { MenuBarItemSeparator } from "@library/MenuBar/MenuBarItemSeparator";
import { useVanillaEditorTable } from "@library/vanilla-editor/VanillaEditorTableContext";
import { convertHeaders } from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { MyTableElement } from "@library/vanilla-editor/typescript";
import { NumberBox } from "@library/forms/NumberBox";
import { Path } from "slate";
import { globalVariables } from "@library/styles/globalStyleVars";
import InputBlock from "@library/forms/InputBlock";
import { cx } from "@emotion/css";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";

/**
 * Toolbar for applying table options to our rich table, it appears when table is selected.
 */
export const TableToolbar = (props: { boundsRef?: MutableRefObject<HTMLDivElement | null | undefined> }) => {
    const editor = useMyEditorState();

    const tableEntry = getTableAbove(editor);
    const tableNode = tableEntry?.[0] as MyTableElement;

    const [tableRows, setTableRows] = useState<number>(tableNode?.children.length);
    const [tableColumns, setTableColumns] = useState<number>(tableNode?.children[0]?.children.length);

    useEffect(() => {
        if (tableNode) {
            setTableRows(tableNode?.children.length);
            setTableColumns(tableNode?.children[0]?.children.length);
        }
    }, [tableNode]);

    const tableID = tableNode?.id;

    const { tablesByID, updateTableState } = useVanillaEditorTable();
    const { tableMeasures, multipleCellsSelected, headerType, contentAlignment } = tablesByID[tableID] ?? {};

    const { boundsRef: editorBounds } = useVanillaEditorBounds();

    const boundsRef = props.boundsRef || editorBounds;

    const [isSubMenuOpen, setIsSubMenuOpen] = useState(false);
    const [activeMenuItemOption, setActiveMenuItemOption] = useState<"table" | "column" | "row" | undefined>();

    // if we are in this component, we know we are in a table
    const currentSelectionPath = editor.selection?.anchor.path;
    const currentColumnPath = currentSelectionPath && currentSelectionPath.slice(0, 3);
    const currentRowPath = currentSelectionPath && currentSelectionPath.slice(0, 2);

    const currentColumnIndex = currentColumnPath?.[currentColumnPath.length - 1] ?? -1;
    const currentRowIndex = currentRowPath?.[currentRowPath.length - 1] ?? -1;

    useEffect(() => {
        if (!tableEntry) {
            updateTableState(tableID, { tableHighlightedArea: undefined });
        }
    }, [tableEntry]);

    useEffect(() => {
        if (isSubMenuOpen) {
            activeMenuItemOption && updateTableState(tableID, { tableHighlightedArea: activeMenuItemOption });
        } else {
            updateTableState(tableID, { tableHighlightedArea: undefined });
        }
    }, [isSubMenuOpen, activeMenuItemOption]);

    useEffect(() => {
        if (contentAlignment) {
            withoutNormalizing(editor, () => {
                setNodes<TTableElement>(editor, { contentAlignment }, { at: tableEntry?.[1] });
            });
        }
    }, [contentAlignment]);

    const triggerTableAction = (actionFn, withNormalizeHeaders?: boolean) => {
        actionFn(editor);
        withNormalizeHeaders && normalizeHeaders();
        focusEditor(editor);
    };

    // after add column/row, we should run through header converters to make sure its the right structure
    // as inserTableRow/insertTableColumn don't take care of headers
    const normalizeHeaders = () => {
        if (headerType !== "top") {
            convertHeaders(editor, headerType);
        }
    };

    const handleAddRemoveRow = (newValue: number) => {
        const lastRow = tableNode.children[tableNode.children.length - 1];
        const lastRowPath = findNodePath(editor, lastRow);
        if (newValue) {
            if (newValue > tableRows) {
                insertTableRow(editor, {
                    fromRow: lastRowPath,
                });
            } else if (tableRows > 1) {
                withoutNormalizing(editor, () => {
                    removeNodes(editor, {
                        at: lastRowPath,
                    });
                });
            }
            normalizeHeaders();
        }
    };

    const handleAddRemoveColumn = (newValue: number) => {
        const lastCellInRow = tableNode.children[0]?.children[tableNode.children[0]?.children.length - 1];
        const lastCellInRowPath = findNodePath(editor, lastCellInRow);
        editor.select(lastCellInRowPath as Path);
        if (newValue > tableColumns) {
            insertTableColumn(editor);
        } else if (tableColumns > 1) {
            deleteColumn(editor);
        }
        normalizeHeaders();
    };

    const toolbarPosition = useMemo(() => {
        if (tableMeasures && tableEntry) {
            const tablePath = tableEntry?.[1];
            const tableRect = getRangeBoundingClientRect(editor, {
                anchor: getStartPoint(editor, tablePath),
                focus: getEndPoint(editor, tablePath),
            });

            // if table is overflowing, we'll center the toolbar in editor
            const isTableOverflowing =
                tableMeasures.actualWidth > (boundsRef?.current?.getBoundingClientRect().width || 0);

            const toolbarLeftPosition = isTableOverflowing
                ? (boundsRef?.current?.getBoundingClientRect().width || 0) / 2 - 72
                : tableMeasures?.actualWidth / 2 + (tableMeasures?.marginLeft ?? 0) - 72;

            return {
                // below the table
                top: tableRect.top - (boundsRef?.current?.getBoundingClientRect().top || 0) + tableRect.height + 1,
                left: toolbarLeftPosition,
            };
        }
        return { top: 0, left: 0 };
    }, [tableID, tableMeasures, tableEntry]);

    const alignmentOptions = (rowsOrColumns: "rows" | "columns") => {
        const elementIndex = rowsOrColumns === "rows" ? currentRowIndex : currentColumnIndex;
        return (
            <MenuBarSubMenuItemGroup
                hasInlineSubMenuItems
                groupTitle={t("Align Content")}
                className={classes.inlineSubMenuGroup}
            >
                <MenuBarSubMenuItem
                    active={contentAlignment?.[rowsOrColumns]?.[elementIndex]?.alignment === "start"}
                    accessibleLabel={t("Align Left")}
                    icon={<Icon icon="align-left" />}
                    onActivate={() => {
                        updateTableState(tableID, {
                            contentAlignment: {
                                ...contentAlignment,
                                [rowsOrColumns]: {
                                    ...contentAlignment?.[rowsOrColumns],
                                    [elementIndex]: {
                                        alignment: "start",
                                        appliedTimestamp: Date.now(),
                                    },
                                },
                            },
                        });
                    }}
                    isInline
                />
                <MenuBarSubMenuItem
                    active={contentAlignment?.[rowsOrColumns]?.[elementIndex]?.alignment === "center"}
                    accessibleLabel={t("Align Center")}
                    icon={<Icon icon="align-center" />}
                    onActivate={() => {
                        updateTableState(tableID, {
                            contentAlignment: {
                                ...contentAlignment,
                                [rowsOrColumns]: {
                                    ...contentAlignment?.[rowsOrColumns],
                                    [elementIndex]: {
                                        alignment: "center",
                                        appliedTimestamp: Date.now(),
                                    },
                                },
                            },
                        });
                    }}
                    isInline
                />
                <MenuBarSubMenuItem
                    active={contentAlignment?.[rowsOrColumns]?.[elementIndex]?.alignment === "end"}
                    accessibleLabel={t("Align Right")}
                    icon={<Icon icon="align-right" />}
                    onActivate={() => {
                        updateTableState(tableID, {
                            contentAlignment: {
                                ...contentAlignment,
                                [rowsOrColumns]: {
                                    ...contentAlignment?.[rowsOrColumns],
                                    [elementIndex]: {
                                        alignment: "end",
                                        appliedTimestamp: Date.now(),
                                    },
                                },
                            },
                        });
                    }}
                    isInline
                />
            </MenuBarSubMenuItemGroup>
        );
    };

    const SubmenuItemTableRows = forwardRef((props: MenuBarSubMenuItem.SubMenuRendererProps, ref) => {
        return (
            <NumberBoxAsMenuBarSubMenuItem
                {...props}
                ref={ref as React.Ref<HTMLInputElement>}
                value={tableRows}
                target="rows"
                valueChangeHandler={handleAddRemoveRow}
            />
        );
    });

    SubmenuItemTableRows.displayName = "MenuBarSubmenuItemTableRows";

    const SubmenuItemTableColumns = forwardRef((props: MenuBarSubMenuItem.SubMenuRendererProps, ref) => {
        return (
            <NumberBoxAsMenuBarSubMenuItem
                {...props}
                ref={ref as React.Ref<HTMLInputElement>}
                value={tableColumns}
                target="columns"
                valueChangeHandler={handleAddRemoveColumn}
            />
        );
    });

    SubmenuItemTableColumns.displayName = "MenuBarSubmenuItemTableColumns";

    if (!toolbarPosition.top || !toolbarPosition.left || multipleCellsSelected) {
        return null;
    }

    return (
        <div
            style={{
                position: "absolute",
                zIndex: 2,
                ...toolbarPosition,
            }}
        >
            <MenuBar className={classes.menuBar}>
                <MenuBarItem
                    accessibleLabel={t("Table Options")}
                    icon={<Icon icon="table" />}
                    onActivate={() => {
                        setActiveMenuItemOption("table");
                    }}
                    onSubMenuVisibilityChange={(isOpen) => {
                        if (isOpen !== isSubMenuOpen) {
                            setIsSubMenuOpen(isOpen);
                        }
                    }}
                >
                    <MenuBarSubMenuItemGroup>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-top-header" />}
                            active={headerType === "top"}
                            onActivate={() => {
                                convertHeaders(editor, "top");
                                updateTableState(tableID, { headerType: "top" });
                            }}
                        >
                            {t("Top Headers")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-left-header" />}
                            active={headerType === "left"}
                            onActivate={() => {
                                convertHeaders(editor, "left");
                                updateTableState(tableID, { headerType: "left" });
                            }}
                        >
                            {t("Left Headers")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-top-left-header" />}
                            active={headerType === "both"}
                            onActivate={() => {
                                convertHeaders(editor, "both");
                                updateTableState(tableID, { headerType: "both" });
                            }}
                        >
                            {t("Top and Left Headers")}
                        </MenuBarSubMenuItem>
                    </MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItemGroup>
                        <MenuBarSubMenuItem
                            subMenuItemRenderer={SubmenuItemTableRows as MenuBarSubMenuItem.SubMenuRenderer}
                        />
                        <MenuBarSubMenuItem
                            subMenuItemRenderer={SubmenuItemTableColumns as MenuBarSubMenuItem.SubMenuRenderer}
                        />
                    </MenuBarSubMenuItemGroup>
                </MenuBarItem>
                <MenuBarItemSeparator />
                <MenuBarItem
                    accessibleLabel={t("Column Options")}
                    icon={<Icon icon="table-column" />}
                    onActivate={() => {
                        setActiveMenuItemOption("column");
                    }}
                    onSubMenuVisibilityChange={(isOpen) => {
                        if (isOpen !== isSubMenuOpen) {
                            setIsSubMenuOpen(isOpen);
                        }
                    }}
                >
                    <MenuBarSubMenuItemGroup>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-add-column-left" />}
                            onActivate={() => {
                                if (currentColumnPath) {
                                    insertTableColumn(editor, { at: currentColumnPath });
                                    normalizeHeaders();
                                    focusEditor(editor, [...currentColumnPath, 0, 0]);
                                }
                            }}
                        >
                            {t("Insert Column To Left")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-add-column-right" />}
                            onActivate={() => {
                                triggerTableAction(insertTableColumn, true);
                            }}
                        >
                            {t("Insert Column To Right")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="delete" />}
                            onActivate={() => {
                                triggerTableAction(deleteColumn);
                            }}
                        >
                            {t("Delete Column")}
                        </MenuBarSubMenuItem>
                    </MenuBarSubMenuItemGroup>
                    {alignmentOptions("columns")}
                </MenuBarItem>
                <MenuBarItemSeparator />
                <MenuBarItem
                    accessibleLabel={t("Row Options")}
                    icon={<Icon icon="table-row" />}
                    onActivate={() => {
                        setActiveMenuItemOption("row");
                    }}
                    onSubMenuVisibilityChange={(isOpen) => {
                        if (isOpen !== isSubMenuOpen) {
                            setIsSubMenuOpen(isOpen);
                        }
                    }}
                >
                    <MenuBarSubMenuItemGroup>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-add-row-above" />}
                            onActivate={() => {
                                if (currentRowPath) {
                                    insertTableRow(editor, { at: currentRowPath });
                                    normalizeHeaders();
                                    focusEditor(editor, [...currentRowPath, 0, 0, 0]);
                                }
                            }}
                        >
                            {t("Insert Row Above")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="table-add-row-below" />}
                            onActivate={() => {
                                triggerTableAction(insertTableRow, true);
                            }}
                        >
                            {t("Insert Row Below")}
                        </MenuBarSubMenuItem>
                        <MenuBarSubMenuItem
                            icon={<Icon icon="delete" />}
                            onActivate={() => {
                                triggerTableAction(deleteRow);
                            }}
                        >
                            {t("Delete Row")}
                        </MenuBarSubMenuItem>
                    </MenuBarSubMenuItemGroup>
                    {alignmentOptions("rows")}
                </MenuBarItem>
                <MenuBarItemSeparator />
                <MenuBarItem
                    accessibleLabel={t("Delete Table")}
                    icon={<Icon icon="delete" />}
                    onActivate={() => {
                        triggerTableAction(deleteTable);
                    }}
                ></MenuBarItem>
            </MenuBar>
        </div>
    );
};

const classes = {
    menuBar: css({ marginTop: 0 }),
    inlineSubMenuGroup: css({ justifyContent: "space-between" }),
    subMenuItemAsInputLabel: css({ fontWeight: globalVariables().fonts.weights.normal }),
    subMenuItemAsInput: css({
        display: "flex",
        alignItems: "center",
        "& > span": { margin: 0 },
        "&:not(:disabled):not([aria-disabled='true'])": {
            "&:hover, &:focus": {
                background: "none",
                color: "inherit",
            },
        },
        cursor: "default",
        "& + &": {
            marginTop: 4,
        },
    }),
};

const NumberBoxAsMenuBarSubMenuItem = forwardRef(
    (
        props: MenuBarSubMenuItem.SubMenuRendererProps & {
            // our props
            target: "rows" | "columns";
            value: number;
            valueChangeHandler: (newValue: number) => void;
        },
        ref: React.RefObject<any>,
    ) => {
        const { value, target, valueChangeHandler } = props;
        const label = target === "rows" ? "Rows" : "Columns";

        return (
            <InputBlock
                label={t(label)}
                className={cx(menuBarClasses().subMenuItem, classes.subMenuItemAsInput, "subMenuItem", {
                    active: props.active,
                })}
                labelClassName={classes.subMenuItemAsInputLabel}
            >
                <NumberBox
                    ref={ref}
                    tabIndex={props.tabIndex}
                    disabled={props["aria-disabled"]}
                    size="small"
                    value={value}
                    onChange={(newValue) => {
                        valueChangeHandler(parseInt(newValue));
                    }}
                    aria-label={`Current ${label}: ${value}. Press Enter to increase, Shift + Enter to decrease.`}
                    onKeyDown={(e) => {
                        if (e.shiftKey && e.key === "Enter" && value > 1) {
                            props.valueChangeHandler(value - 1);
                        } else if (e.key === "Enter") {
                            props.valueChangeHandler(value + 1);
                        }
                        props.onKeyDown(e);
                    }}
                    min={1}
                />
            </InputBlock>
        );
    },
);

NumberBoxAsMenuBarSubMenuItem.displayName = "NumberBoxAsMenuBarSubMenuItem";
