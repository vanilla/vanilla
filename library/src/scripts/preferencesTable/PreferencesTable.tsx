/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { TableType } from "@library/notificationPreferences";

export function PreferencesTable(props: { table: TableType }) {
    const formClasses = notificationPreferencesFormClasses();

    const { getTableProps, getTableBodyProps, headerGroups, rows, prepareRow } = props.table;

    return (
        <div className={formClasses.tableWrapper}>
            <table {...getTableProps()}>
                <thead>
                    {headerGroups.map((headerGroup) => {
                        const { key, ...headerGroupProps } = headerGroup.getHeaderGroupProps();
                        return (
                            <tr {...headerGroupProps} key={key}>
                                {headerGroup.headers.map((column) => {
                                    const { key, ...headerProps } = column.getHeaderProps();
                                    return (
                                        <th {...headerProps} key={key} className={formClasses.tableHeader}>
                                            {column.render("Header")}
                                        </th>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </thead>
                <tbody {...getTableBodyProps()}>
                    {rows.map((row, i) => {
                        prepareRow(row);
                        const { key, ...rowProps } = row.getRowProps();
                        return (
                            <tr {...rowProps} key={key} className={formClasses.tableRow}>
                                {row.cells.map((cell) => {
                                    const { key, ...cellProps } = cell.getCellProps();
                                    return (
                                        <td {...cellProps} key={key} className={formClasses.tableCell}>
                                            {cell.render("Cell")}
                                        </td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
