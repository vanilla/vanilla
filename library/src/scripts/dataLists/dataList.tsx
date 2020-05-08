/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { dataListClasses } from "@library/dataLists/dataListStyles";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";

export interface IData {
    key: React.ReactNode;
    value: React.ReactNode;
}

export interface IDataList {
    className?: string;
    data: IData[];
}

/**
 * Component for displaying data lists
 * Because of accessibility concerns, the markup is a table not a data list.
 */
export function DataList(props: IDataList) {
    const classes = dataListClasses();
    if (props.data.length === 0) {
        return null;
    }
    return (
        <div className={classNames(classes.root, props.className)}>
            <table className={classes.table}>
                <caption>{t("Event Details")}</caption>
                {props.data.map((d, i) => {
                    return (
                        <tr
                            className={classNames(classes.row, { isFirst: i === 0, isLast: i === props.data.length })}
                            key={i}
                        >
                            <th scope="row" className={classes.key}>
                                {d.key}
                            </th>
                            <td className={classes.value}>{d.value}</td>
                        </tr>
                    );
                })}
            </table>
        </div>
    );
}
