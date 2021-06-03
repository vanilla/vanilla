/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { visibility } from "@library/styles/styleHelpers";
import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@emotion/css";

interface ICommonProps {
    title: string;
    status?: string;
    className?: string;
}

export type IProps = ICommonProps &
    (
        | {
              action(event: any): any;
              actionLabel: string;
              actionIcon: React.ReactNode | string;
          }
        | {
              action?: false;
              actionLabel?: never;
              actionIcon?: never;
          }
    );

export const DashboardFormListItem = (props: IProps) => {
    const { title, status, action, actionLabel, actionIcon, className } = props;
    const classes = dashboardClasses();
    return (
        <li className={cx(classes.formListItem, className)}>
            <span className={classes.formListItemTitle}>{title}</span>
            {status && <span className={classes.formListItemStatus}>{status}</span>}
            {action && (
                <span className={classes.formListItemAction}>
                    <Button onClick={action} ariaLabel={actionLabel} buttonType={ButtonTypes.ICON_COMPACT}>
                        {actionIcon}
                        <span className={visibility().visuallyHidden}>{actionLabel}</span>
                    </Button>
                </span>
            )}
        </li>
    );
};
