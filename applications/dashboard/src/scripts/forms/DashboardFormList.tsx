/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { dashboardFormListClasses } from "@dashboard/forms/DashboardFormListStyles";

export function DashboardFormList(props) {
    const classes = dashboardFormListClasses();
    return <ul className={classNames(classes.root)}>{props.children}</ul>;
}
