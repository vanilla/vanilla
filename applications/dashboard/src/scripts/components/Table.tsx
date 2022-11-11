/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useEffect, useMemo, useRef, useState } from "react";
import { tableClasses } from "./Table.styles";
import {
    Column,
    HeaderGroup,
    Row,
    TableInstance,
    useColumnOrder,
    usePagination,
    useSortBy,
    useTable,
} from "react-table";
import { DashboardPager } from "@dashboard/components/DashboardPager";
import { cx } from "@emotion/css";
import { Scrollbars } from "react-custom-scrollbars";
import TruncatedText from "@library/content/TruncatedText";
import { getJSLocaleKey } from "@vanilla/i18n";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import ConditionalWrap from "@library/layout/ConditionalWrap";

export interface ITableHeaderProps {
    headerGroups: HeaderGroup[];
    sortable?: boolean;
    rows: Row[];
    headerClassNames?: string;
    rowClassNames?: string;
    hiddenHeaders?: string[];
}

const TableHeader = (props: ITableHeaderProps) => {
    const { headerGroups, sortable, rows, headerClassNames, rowClassNames, hiddenHeaders } = props;
    const classes = tableClasses();
    const valueTypeLookUp = useMemo<Record<string, string>>(
        () => Object.fromEntries(Object.keys(rows[0].original).map((key) => [key, typeof rows[0].original[key]])),
        [rows],
    );

    return (
        <thead className={headerClassNames}>
            {headerGroups.map((headerGroup, groupIndex) => (
                <tr
                    {...headerGroup.getHeaderGroupProps()}
                    key={`header-group-${groupIndex}`}
                    className={cx(classes.row, rowClassNames)}
                >
                    {headerGroup.headers.map((column, columnIndex) => {
                        return (
                            <th
                                {...column.getHeaderProps(sortable ? column.getSortByToggleProps() : undefined)}
                                key={`header-group-${columnIndex}`}
                                className={cx(
                                    classes.head,
                                    classes.basicColumn,
                                    columnIndex === 0 && classes.leftAlignHead,
                                    valueTypeLookUp[`${column.Header}`] === "string" && classes.leftAlignHead,
                                )}
                            >
                                <ConditionalWrap
                                    condition={hiddenHeaders?.includes(column.id) ?? false}
                                    component={ScreenReaderContent}
                                >
                                    {column.render("Header")}
                                </ConditionalWrap>
                                <span>{column.isSorted ? (column.isSortedDesc ? " ▼" : " ▲") : ""}</span>
                            </th>
                        );
                    })}
                </tr>
            ))}
        </thead>
    );
};

export interface ITableRowProps {
    rows: Row[];
    prepareRow(arg0: Row): void;
    rowClassNames?: string;
    cellClassNames?: string;
}

const TableRows = (props: ITableRowProps) => {
    const { rows, prepareRow, rowClassNames, cellClassNames } = props;
    const classes = tableClasses();

    return (
        <>
            {rows.map((row, rowIndex) => {
                prepareRow(row);

                return (
                    <tr {...row.getRowProps()} key={`body-${rowIndex}`} className={cx(classes.row, rowClassNames)}>
                        {row.cells.map((cell, cellIndex) => {
                            return (
                                <td
                                    {...cell.getCellProps()}
                                    key={`body-${cellIndex}`}
                                    className={cx(
                                        classes.cell,
                                        classes.basicColumn,
                                        cellIndex === 0 && classes.leftAlign,
                                        typeof cell.value === "string" && classes.leftAlign,
                                        cellClassNames,
                                    )}
                                >
                                    <span className={classes.cellContentWrap}>
                                        <TruncatedText lines={2}>{cell.render("Cell")}</TruncatedText>
                                    </span>
                                </td>
                            );
                        })}
                    </tr>
                );
            })}
        </>
    );
};

// Conditionally wrap child nodes
const TableWrap = ({ condition, wrapper, children }) => (condition ? wrapper(children) : children);

export interface ITableData {
    [key: string]: string | number | Date | ReactNode | undefined;
}

export interface ISortOption {
    id: string;
    desc: boolean;
}

interface ICustomCellRender {
    columnName: string[];
    component: ReactNode;
}

export interface ITableProps {
    data: ITableData[];
    customColumnOrder?: string[];
    sortable?: boolean;
    customColumnSort?: ISortOption;
    paginate?: boolean;
    pageSize?: number;
    customCellRenderer?: ICustomCellRender[];
    hiddenColumns?: string[];
    hiddenHeaders?: string[];
    rowHeight?(size: number): void;
    tableClassNames?: string;
    headerClassNames?: string;
    rowClassNames?: string;
    cellClassNames?: string;
}

export const Table = (props: ITableProps) => {
    const {
        data,
        customColumnOrder,
        sortable,
        customColumnSort,
        paginate,
        pageSize,
        customCellRenderer,
        hiddenColumns,
        hiddenHeaders,
        rowHeight,
        tableClassNames,
        headerClassNames,
        rowClassNames,
        cellClassNames,
    } = props;

    const classes = tableClasses();

    // Columns need to be memoized and at minimum have a Header and accessor keys
    // https://react-table.tanstack.com/docs/quick-start#define-columns
    const memoizedColumns: Array<Column<ITableData>> = useMemo(() => {
        if (data && data.length > 0) {
            const columnKeys = Object.keys(data[0]);
            return columnKeys
                .filter((key) => (hiddenColumns ? !hiddenColumns.includes(key) : true))
                .map((key) => {
                    let customCell = {};
                    if (customCellRenderer) {
                        const cellConfig = customCellRenderer.find((entry) => entry.columnName.includes(key));
                        if (cellConfig) {
                            customCell = { Cell: cellConfig.component };
                        }
                    }

                    return {
                        Header: key,
                        // keys could sometimes contain dots which break the cell render
                        accessor: (value) => value[`${key}`],
                        // Set the default cell render to format numbers
                        // https://react-table.tanstack.com/docs/api/useTable
                        Cell: ({ row }: TableInstance) => {
                            if (typeof row.original[key] === "number") {
                                return new Intl.NumberFormat(getJSLocaleKey()).format(row.original[key]);
                            }
                            return row.original[key];
                        },
                        ...customCell,
                    };
                });
        }
        return [];
    }, [data, hiddenColumns]);

    // Memoize data to prevent react-table from recalculating props (Deep equality is expensive)
    const memoizedData: any[] = useMemo(() => data, [data]);

    // React Table
    const {
        getTableProps,
        getTableBodyProps,
        headerGroups,
        prepareRow,
        columns,
        setColumnOrder,
        setSortBy,
        // Rows and page are similar, rows contain all rows, page contains rows for the page that is set
        // Access page information from the state object
        rows,
        page,
        canPreviousPage,
        canNextPage,
        pageOptions,
        pageCount,
        gotoPage,
        setPageSize,
        setHiddenColumns,
        state, // We can access the table state from this object
    } = useTable(
        {
            columns: memoizedColumns,
            data: memoizedData,
        },
        useColumnOrder,
        useSortBy,
        usePagination,
    );

    const wrapperRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (wrapperRef && rowHeight) {
            const domElement = wrapperRef.current;
            if (domElement) {
                const firstBodyRowHeight: number =
                    domElement.querySelector("tbody tr")?.getBoundingClientRect().height ?? 0;
                rowHeight(firstBodyRowHeight);
            }
        }
    }, [wrapperRef, rowHeight]);

    // If a custom column order is defined (Default is object shape)
    useEffect(() => {
        if (customColumnOrder && customColumnOrder.length > 0) {
            setColumnOrder(customColumnOrder);
        }
    }, [customColumnOrder, setColumnOrder]);

    // If a custom sort order is defined, enforce it (Default is none)
    useEffect(() => {
        if (customColumnSort) {
            setSortBy([customColumnSort]);
        }
    }, [setSortBy, customColumnSort]);

    // Enable pagination
    useEffect(() => {
        if (pageSize && pageSize > 0) {
            setPageSize(pageSize);
        }
    }, [pageSize, setPageSize]);

    // Hidden Columns
    useEffect(() => {
        if (hiddenColumns) {
            setHiddenColumns(hiddenColumns);
        }
    }, [hiddenColumns, setHiddenColumns]);

    const showPaginationControls = useMemo(() => {
        return paginate && (canNextPage || canPreviousPage);
    }, [paginate, canNextPage, canPreviousPage]);

    return (
        <>
            <TableWrap
                condition={paginate}
                wrapper={(children: React.ReactChildren) => (
                    <section ref={wrapperRef} className={classes.layoutWrap}>
                        {children}
                    </section>
                )}
            >
                <TableWrap
                    condition={paginate}
                    wrapper={(children: React.ReactChildren) => (
                        <section className={classes.tableWrap}>
                            <Scrollbars
                                style={{ width: "100%", height: "100%" }}
                                renderThumbHorizontal={(props) => <div {...props} className={classes.scrollThumb} />}
                                renderTrackVertical={() => <span />}
                            >
                                {children}
                            </Scrollbars>
                        </section>
                    )}
                >
                    <table {...getTableProps()} className={cx(classes.table, tableClassNames)}>
                        <TableHeader
                            headerGroups={headerGroups}
                            sortable={sortable}
                            rows={paginate ? page : rows}
                            headerClassNames={headerClassNames}
                            rowClassNames={rowClassNames}
                            hiddenHeaders={hiddenHeaders}
                        />
                        <tbody {...getTableBodyProps}>
                            <TableRows
                                rows={paginate ? page : rows}
                                prepareRow={prepareRow}
                                rowClassNames={rowClassNames}
                                cellClassNames={cellClassNames}
                            />
                        </tbody>
                    </table>
                </TableWrap>
                {showPaginationControls && (
                    <TableWrap
                        condition={paginate}
                        wrapper={(children: React.ReactChildren) => (
                            <section className={classes.paginationWrap}>{children}</section>
                        )}
                    >
                        <DashboardPager
                            page={state.pageIndex + 1}
                            pageCount={pageCount}
                            onClick={(pageIndex) => gotoPage(pageIndex - 1)}
                            className={classes.pagination}
                        />
                    </TableWrap>
                )}
            </TableWrap>
        </>
    );
};
