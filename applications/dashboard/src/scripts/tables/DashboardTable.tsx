/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { DashboardTableHeadItem } from "@dashboard/tables/DashboardTableHeadItem";

interface IProps {
    head: React.ReactNode;
    body: React.ReactNode;
}

export function DashboardTable(props: IProps) {
    return (
        <div className="table-wrap">
            <table className="table-data">
                <thead>{props.head}</thead>
                <tbody>{props.body}</tbody>
            </table>
        </div>
    );
}

DashboardTable.HeadItem = DashboardTableHeadItem;
