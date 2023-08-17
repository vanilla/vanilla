/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentType, useEffect, useMemo, useRef, useState } from "react";
import { tableClasses } from "@dashboard/components/Table.styles";
import { IUser } from "@library/@types/api/users";
import { cx } from "@emotion/css";
import { useMeasure } from "@vanilla/react-utils";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { t } from "@vanilla/i18n";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { Icon } from "@vanilla/icons";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { stackableTableClasses } from "@dashboard/tables/StackableTable/StackableTable.classes";
import { spaceshipCompare } from "@vanilla/utils";

export interface ColumnConfig {
    order: number;
    wrapped: boolean;
    isHidden: boolean;
    sortDirection?: StackableTableSortOption;
    columnID?: string; //this one is mainly for profile fields, for static columns this does not exist as key/name is already the id
    width?: number;
}

export type StackableTableColumnsConfig = Record<string, ColumnConfig>;

export enum StackableTableSortOption {
    ASC = "asc",
    DESC = "desc",
    NO_SORT = "noSort",
}

interface IStackableTableProps {
    data: Array<Record<string, any>>;
    updateQuery: (newQueryParams: any) => void;
    onHeaderClick: (columnName: string, sortOption: string) => void;
    hiddenHeaders?: string[];
    isLoading?: boolean;
    columnsConfiguration: Record<string, ColumnConfig>;
    CellRenderer: ComponentType<any>;
    WrappedCellRenderer: ComponentType<any>;
    ActionsCellRenderer?: ComponentType<any>;
    className?: string;
    actionsColumnWidth?: number;
    headerWrappers?: Record<string, ComponentType<any>>;
}

export interface IStackableTableHeaderProps {
    headers: string[];
    sortable?: boolean;
    columnsConfiguration: StackableTableColumnsConfig;
    headerClassNames?: string;
    rowClassNames?: string;
    hiddenHeaders?: string[];
    onClick?: (param: any) => void;
    actionsColumnWidth?: number;
    headerWrappers?: Record<string, ComponentType<any>>;
}

interface IStackableTableRowsProps extends Omit<IStackableTableProps, "onHeaderClick" | "hiddenHeaders"> {
    orderedColumns: string[];
}

const DEFAULT_FIRST_COLUMN_WIDTH = 240;
const DEFAULT_COLUMN_WIDTH = 140;
const DEFAULT_COLUMN_MAX_WIDTH = 160;

const StackableTableHeader = (props: IStackableTableHeaderProps) => {
    const {
        headers,
        headerClassNames,
        rowClassNames,
        hiddenHeaders,
        onClick,
        columnsConfiguration,
        actionsColumnWidth,
        headerWrappers,
    } = props;

    const classes = stackableTableClasses(actionsColumnWidth);

    return (
        <thead className={headerClassNames}>
            <tr className={cx(tableClasses().row, classes.tableRow, rowClassNames)} role="row">
                {headers.map((header, key) => {
                    const wrappedColumn = columnsConfiguration[header] && columnsConfiguration[header].wrapped;
                    const sortDirection = columnsConfiguration[header] && columnsConfiguration[header].sortDirection;
                    const isSortable = !!sortDirection;
                    const hasWrapper = !!headerWrappers;
                    const customWidth = columnsConfiguration[header] && columnsConfiguration[header].width;
                    return !wrappedColumn ? (
                        <th
                            key={key}
                            className={cx(tableClasses().head)}
                            role="columnheader"
                            style={
                                customWidth ? { minWidth: `${customWidth}px`, width: `${customWidth}px` } : undefined
                            }
                        >
                            <ConditionalWrap
                                condition={hasWrapper}
                                component={headerWrappers && headerWrappers[header]}
                            >
                                <ConditionalWrap
                                    condition={isSortable}
                                    tag={"span"}
                                    componentProps={{
                                        role: "button",
                                        tabIndex: 0,
                                        onClick: () => {
                                            isSortable && onClick && onClick(header);
                                        },
                                        onKeyDown: (event) => {
                                            if (isSortable && onClick && event.key === "Enter") {
                                                event.preventDefault();
                                                onClick(header);
                                            }
                                        },
                                    }}
                                    className={classes.sortableHead}
                                >
                                    <ConditionalWrap
                                        condition={hiddenHeaders?.includes(header) ?? false}
                                        component={ScreenReaderContent}
                                    >
                                        {t(header)}
                                    </ConditionalWrap>
                                    {isSortable && sortDirection !== StackableTableSortOption.NO_SORT ? (
                                        <span>
                                            {sortDirection === StackableTableSortOption.DESC ? (
                                                <Icon icon={"data-down"} />
                                            ) : (
                                                <Icon icon={"data-up"} />
                                            )}
                                        </span>
                                    ) : (
                                        <span className={classes.sortIconSpacer}></span>
                                    )}
                                </ConditionalWrap>
                            </ConditionalWrap>
                        </th>
                    ) : (
                        <React.Fragment key={key}></React.Fragment>
                    );
                })}
            </tr>
        </thead>
    );
};

const StackableTableRows = (props: IStackableTableRowsProps) => {
    const {
        data,
        orderedColumns,
        columnsConfiguration,
        updateQuery,
        isLoading,
        CellRenderer,
        WrappedCellRenderer,
        ActionsCellRenderer,
        actionsColumnWidth,
    } = props;
    const classes = stackableTableClasses(actionsColumnWidth);
    let rows;

    if (isLoading) {
        const loadingRectangleArray = Array.from(new Array("loadingRectangle".length));
        const notWrappedColumns = orderedColumns.filter((column) => !columnsConfiguration[column].wrapped);
        rows = loadingRectangleArray.map((l, key) => {
            return (
                <tr key={key} className={classes.tableRow} role="row">
                    {notWrappedColumns.map((columnName, key) => {
                        const isFirstColumn = columnsConfiguration[columnName].order === 1;
                        return (
                            <td
                                key={key}
                                role="cell"
                                style={
                                    columnsConfiguration[columnName].width
                                        ? {
                                              minWidth: `${columnsConfiguration[columnName].width}px`,
                                              width: `${columnsConfiguration[columnName].width}px`,
                                          }
                                        : undefined
                                }
                            >
                                {isFirstColumn && (
                                    <div className={classes.firstColumnPlaceholder}>
                                        <LoadingRectangle
                                            height={25}
                                            width={25}
                                            style={{ marginRight: 10, borderRadius: "50%" }}
                                        />

                                        <LoadingRectangle height={15} width={160} />
                                    </div>
                                )}
                                {!isFirstColumn && (
                                    <LoadingRectangle
                                        height={15}
                                        width={columnsConfiguration[columnName].width ?? 90}
                                    />
                                )}
                            </td>
                        );
                    })}
                    {ActionsCellRenderer && (
                        <td role="cell">
                            <div style={{ height: "15px", width: "90px" }}></div>
                        </td>
                    )}
                </tr>
            );
        });
        return <tbody>{rows}</tbody>;
    }

    rows = data.map((entry: IUser, key) => {
        return (
            <tr key={key} className={classes.tableRow} role="row">
                {orderedColumns.map((columnName, key) => {
                    const isFirstColumn = columnsConfiguration[columnName].order === 1;
                    const customWidth = columnsConfiguration[columnName].width;
                    if (!columnsConfiguration[columnName].wrapped) {
                        return (
                            <td
                                key={key}
                                role="cell"
                                style={
                                    customWidth
                                        ? { minWidth: `${customWidth}px`, width: `${customWidth}px` }
                                        : undefined
                                }
                            >
                                <ConditionalWrap condition={isFirstColumn} className={cx("first-column")} key={key}>
                                    {CellRenderer && (
                                        <CellRenderer data={entry} columnName={columnName} updateQuery={updateQuery} />
                                    )}
                                    {isFirstColumn && (
                                        <div className={cx(classes.wrappedContent, "wrapped-content")}>
                                            {WrappedCellRenderer && (
                                                <WrappedCellRenderer
                                                    configuration={columnsConfiguration}
                                                    data={entry}
                                                    orderedColumns={orderedColumns}
                                                />
                                            )}
                                        </div>
                                    )}
                                </ConditionalWrap>
                            </td>
                        );
                    }
                })}
                {ActionsCellRenderer && (
                    <td role="cell">
                        <ActionsCellRenderer data={entry} />
                    </td>
                )}
            </tr>
        );
    });

    return <tbody>{rows}</tbody>;
};

/**
 * This component will render a table out of received data and columns configuration.
 * Depending on columns configuration we will have headers order, if there are clickable for sorting etc.
 * Upon reducing available space for the table, columns will stack into first column to support responsiveness.
 * This table does not have default render for cells, we need to explicitely send  CellRenderer/WrappedCellRenderer/ActionsCellRenderer
 *
 */
export default function StackableTable(props: IStackableTableProps) {
    const { onHeaderClick, columnsConfiguration, hiddenHeaders, className, headerWrappers, ...rest } = props;

    const classes = stackableTableClasses();
    const tableRef = useRef<HTMLDivElement>(null);
    const tableMeasure = useMeasure(tableRef);
    const [configuration, setConfiguration] = useState<StackableTableColumnsConfig>(columnsConfiguration);

    const orderedColumns = useMemo(() => {
        return getOrderedColumns(columnsConfiguration);
    }, [columnsConfiguration]);

    const headers = props.ActionsCellRenderer ? [...orderedColumns, "actions"] : orderedColumns;

    useEffect(() => {
        setConfiguration(
            adjustColumnsVisibility(columnsConfiguration, orderedColumns, tableMeasure.width, props.actionsColumnWidth),
        );
    }, [tableMeasure.width, columnsConfiguration]);

    const onTableHeaderClick = (columnName: string) => {
        if (columnName && configuration[columnName] && configuration[columnName].sortDirection) {
            const newConfiguration = { ...configuration };

            //reset other sorts
            orderedColumns.forEach((column) => {
                if (newConfiguration[column].sortDirection && column !== columnName) {
                    newConfiguration[column].sortDirection = StackableTableSortOption.NO_SORT;
                }
            });

            const currentSortDirection = newConfiguration[columnName].sortDirection;
            const newSortDirection =
                currentSortDirection === StackableTableSortOption.DESC
                    ? StackableTableSortOption.ASC
                    : StackableTableSortOption.DESC;
            newConfiguration[columnName].sortDirection = newSortDirection;

            setConfiguration(newConfiguration);
            onHeaderClick(columnName, newSortDirection);
        }
    };

    return (
        <div className={cx(classes.tableContainer, "table-container", className)} ref={tableRef}>
            <table className={cx(tableClasses().table, classes.table)} role="table">
                <StackableTableHeader
                    headers={headers}
                    hiddenHeaders={hiddenHeaders}
                    headerClassNames={cx(classes.tableHeader)}
                    columnsConfiguration={configuration}
                    onClick={onTableHeaderClick}
                    actionsColumnWidth={props.actionsColumnWidth}
                    headerWrappers={headerWrappers}
                />
                <StackableTableRows
                    columnsConfiguration={columnsConfiguration}
                    orderedColumns={orderedColumns}
                    {...rest}
                />
            </table>
        </div>
    );
}

/**
 * This will calculate and adjust "wrapped" value of columns, depending on the available space.
 *
 * @param configuration - Configuration to change.
 * @param orderedColumns - All columns.
 * @param actualWidth - Actual width of visible area for the table.
 * @param actionsColumnWidth - Optional param, in case actions column width is different.
 */
export function adjustColumnsVisibility(
    configuration: StackableTableColumnsConfig,
    orderedColumns: string[],
    actualWidth: number,
    actionsColumnWidth?: number,
) {
    //actions column
    const lastColumnWidth = actionsColumnWidth ?? DEFAULT_COLUMN_WIDTH;

    if (actualWidth > 0) {
        //total width, minus first column and actions column
        const spaceForVisibleColumns = actualWidth - DEFAULT_FIRST_COLUMN_WIDTH - lastColumnWidth;

        //some logic to adjust the wrapped value in configuration
        const columnsWithCustomWidth = orderedColumns.filter(
            (column) => configuration[column].width && !configuration[column].wrapped,
        );
        const customWidth = columnsWithCustomWidth[0] && configuration[columnsWithCustomWidth[0]].width;
        const averageColumnWidth = customWidth
            ? (((DEFAULT_COLUMN_MAX_WIDTH + DEFAULT_COLUMN_WIDTH) / 2) *
                  (orderedColumns.length - columnsWithCustomWidth.length) +
                  columnsWithCustomWidth.length * customWidth) /
              orderedColumns.length
            : (DEFAULT_COLUMN_MAX_WIDTH + DEFAULT_COLUMN_WIDTH) / 2;
        const notWrappedColumnsNumber =
            spaceForVisibleColumns > 0 ? Math.floor(spaceForVisibleColumns / averageColumnWidth) : 0;
        const newConfiguration = { ...configuration };
        const reverseOrderColumns = [...orderedColumns].reverse();
        const notWrappedColumns = [orderedColumns[0], ...reverseOrderColumns.slice(0, notWrappedColumnsNumber)];

        orderedColumns.forEach((column: string) => {
            if (newConfiguration[column]) {
                newConfiguration[column].wrapped = notWrappedColumns.includes(column) ? false : true;
            }
        });
        return newConfiguration;
    }

    return configuration;
}

/**
 * This generates an array of not hidden columns by their order value in configuration.
 *
 * @param configuration - Columns configuration.
 */
export const getOrderedColumns = (configuration: StackableTableColumnsConfig): string[] => {
    const columns = Object.keys(configuration).sort((columnA, columnB) => {
        const columnAOrder = configuration[columnA].order;
        const columnBOrder = configuration[columnB].order;
        return spaceshipCompare(columnAOrder, columnBOrder);
    });

    return columns.filter((column) => !configuration[column].isHidden);
};
