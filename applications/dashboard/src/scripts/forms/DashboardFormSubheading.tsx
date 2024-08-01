/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    hasBackground?: boolean;
    description?: string;
    actions?: React.ReactNode;
}

export function DashboardFormSubheading(props: IProps) {
    const { hasBackground, actions, description, children, ...restProps } = props;
    const classes = dashboardClasses();
    return (
        <li>
            <h2
                {...restProps}
                className={cx(
                    "subheading",
                    classes.subHeading,
                    props.className,
                    hasBackground && classes.subHeadingBackground,
                )}
            >
                {children}
                {actions && <div className={classes.subHeadingActions}>{actions}</div>}
            </h2>
        </li>
    );
}
