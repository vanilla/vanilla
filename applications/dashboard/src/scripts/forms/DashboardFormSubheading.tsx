/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    hasBackground?: boolean;
}

export function DashboardFormSubheading(props: IProps) {
    const { hasBackground, ...restProps } = props;
    return (
        <li>
            <h2
                {...restProps}
                className={cx("subheading", props.className, hasBackground && dashboardClasses().subHeadingBackground)}
            />
        </li>
    );
}
