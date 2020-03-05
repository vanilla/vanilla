/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

export function DashboardFormSubheading(props: IProps) {
    return (
        <li>
            <h2 {...props} className={classNames("subheading", props.className)}></h2>
        </li>
    );
}
