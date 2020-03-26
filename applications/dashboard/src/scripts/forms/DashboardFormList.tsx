/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

export function DashboardFormList(props: { children: React.ReactNode; isBlurred?: boolean }) {
    const classes = dashboardClasses();
    return (
        <ul
            className={classNames(classes.formList, props.isBlurred && "foggy")}
            aria-hidden={props.isBlurred ? true : false}
        >
            {props.children}
        </ul>
    );
}
