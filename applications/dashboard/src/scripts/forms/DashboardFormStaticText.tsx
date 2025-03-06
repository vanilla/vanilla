/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { cx } from "@emotion/css";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    label: ReactNode;
}

export function DashboardFormStaticText(props: IProps) {
    const classes = dashboardClasses();
    return <p className={cx("staticText", classes.staticText, props.className)}>{props.label}</p>;
}
