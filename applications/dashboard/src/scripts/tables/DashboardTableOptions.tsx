/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    children: React.ReactNode;
}

export function DashboardTableOptions(props: IProps) {
    return (
        <div className="options">
            <div className="btn-group">{props.children}</div>
        </div>
    );
}
