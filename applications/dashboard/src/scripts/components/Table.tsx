/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useEffect, useMemo, useRef } from "react";
import { tableClasses } from "./Table.styles";
import {
    Column,
    HeaderGroup,
    Row,
    SortByFn,
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
import { Icon } from "@vanilla/icons";

export interface ITableHeaderProps {
    headerGroups: HeaderGroup[];
    sortable?: boolean;
    columnsNotSortable?: string[];
    rows: Row[];
    headerClassNames?: string;
    rowClassNames?: string;
    hiddenHeaders?: string[];
}

const TableHeader = (props: ITableHeaderProps) => {
    const { headerGroups, sortable, columnsNotSortable, rows, headerClassNames, rowClassNames, hiddenHeaders } = props;
    const classes = tableClasses();
    const valueTypeLookUp = useMemo<Record<string, string>>(() => {
        if (rows?.[0]?.original) {
            return Object.fromEntries(
                Object.keys(rows?.[0]?.original).map((key) => [key, typeof rows?.[0]?.original?.[key]]),
            );
        }
        return {};
    }, [rows]);

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
                                {...column.getHeaderProps(
                                    sortable && !columnsNotSortable?.includes(column.id)
                                        ? column.getSortByToggleProps()
                                        : undefined,
                                )}
                                key={`header-group-${columnIndex}`}
                                className={cx(
                                    classes.head,
                                    classes.basicColumn,
                                    columnIndex === 0 && classes.leftAlignHead,
                                    sortable && !columnsNotSortable?.includes(column.id) && classes.isSortHead,
                                    column.isSorted && classes.isSortedHead,
                                    valueTypeLookUp[`${column.Header}`] === "string" && classes.leftAlignHead,
                                )}
                                {...(column.isSorted && {
                                    "aria-sort": column.isSortedDesc ? "descending" : "ascending",
                                })}
                            >
                                <ConditionalWrap
                                    condition={column.isSorted}
                                    tag={"button"}
                                    componentProps={{
                                        onClick: (e) => {
                                            e.preventDefault();
                                        },
                                    }}
                                >
                                    <ConditionalWrap
                                        condition={hiddenHeaders?.includes(column.id) ?? false}
                                        component={ScreenReaderContent}
                                    >
                                        {column.render("Header")}
                                    </ConditionalWrap>
                                    <Icon icon={column.isSortedDesc ? "data-down" : "data-up"} />
                                </ConditionalWrap>
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
    truncateCells: boolean;
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
                            const cellContent = cell.render("Cell");
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
                                    <span
                                        className={
                                            props.truncateCells
                                                ? classes.cellContentWrapTruncated
                                                : classes.cellContentWrap
                                        }
                                    >
                                        {props.truncateCells ? (
                                            <TruncatedText className={classes.cellContentTruncate} lines={2}>
                                                {cellContent}{" "}
                                            </TruncatedText>
                                        ) : (
                                            cellContent
                                        )}
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
    [key: string]: string | number | Date | ReactNode | JSX.Element | undefined;
}

export interface ISortOption {
    id: string;
    desc: boolean;
}

interface ICustomCellRender {
    columnName: string[];
    component: ReactNode | ((props: TableInstance<{}>) => ReactNode);
}

export interface ITableProps {
    data: ITableData[];
    customColumnOrder?: string[];
    sortable?: boolean;
    columnsNotSortable?: string[];
    customColumnSort?: ISortOption;
    customSortByFnPerColumn?: Record<string, SortByFn<any>>;
    /** Specify column sizes in an array of numbers, representing percentages */
    columnSizes?: number[];
    onSortChange?: (sortOptions) => void;
    initialSortBy?: ISortOption[];
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
    truncateCells?: boolean;
}

export const Table = (props: ITableProps) => {
    const {
        data,
        customColumnOrder,
        sortable,
        columnsNotSortable,
        customColumnSort,
        customSortByFnPerColumn,
        onSortChange,
        initialSortBy,
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
        truncateCells = true,
        columnSizes,
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
                        ...(Object.keys(customSortByFnPerColumn ?? {}).includes(key) && {
                            sortType: customSortByFnPerColumn?.[key],
                        }),
                        sortDescFirst: true,
                        ...customCell,
                    };
                });
        }
        return [];
    }, [data, hiddenColumns, customCellRenderer]);

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
            initialState: {
                sortBy: initialSortBy ?? [],
            },
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

    // Listen to sort changes
    useEffect(() => {
        if (onSortChange) {
            onSortChange(state.sortBy);
        }
    }, [state.sortBy, onSortChange]);

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
                wrapper={(children: React.ReactNode) => (
                    <section ref={wrapperRef} className={classes.layoutWrap}>
                        {children}
                    </section>
                )}
            >
                <TableWrap
                    condition={paginate}
                    wrapper={(children: React.ReactNode) => (
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
                        {columnSizes && (
                            <colgroup>
                                {columnSizes.map((size, index) => (
                                    <col key={`col-${index}`} style={{ width: `${size}%` }} />
                                ))}
                            </colgroup>
                        )}
                        <TableHeader
                            headerGroups={headerGroups}
                            sortable={sortable}
                            columnsNotSortable={columnsNotSortable}
                            rows={paginate ? page : rows}
                            headerClassNames={headerClassNames}
                            rowClassNames={rowClassNames}
                            hiddenHeaders={hiddenHeaders}
                        />
                        <tbody {...getTableBodyProps()}>
                            <TableRows
                                rows={paginate ? page : rows}
                                prepareRow={prepareRow}
                                rowClassNames={rowClassNames}
                                cellClassNames={cellClassNames}
                                truncateCells={truncateCells}
                            />
                        </tbody>
                    </table>
                </TableWrap>
                {showPaginationControls && (
                    <TableWrap
                        condition={paginate}
                        wrapper={(children: React.ReactNode) => (
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
