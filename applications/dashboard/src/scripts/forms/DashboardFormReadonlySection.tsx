/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import React from "react";

interface IProps {
    title: React.ReactNode;
    description: React.ReactNode;
    tokens?: React.ReactNode[];
    actions?: React.ReactNode;
    emptyMessage?: string;
}

export function DashboardFormReadOnlySection(props: IProps) {
    const classes = dashboardClasses();
    return (
        <>
            <DashboardFormSubheading hasBackground actions={props.actions}>
                {props.title}
            </DashboardFormSubheading>
            <div className={classes.readonlyRow}>
                <p className={cx("info", classes.readonlyDescription)}>{props.description}</p>
                {props.tokens && (
                    <div className={classes.readonlyTokens}>
                        {props.tokens.length === 0 && (
                            <p className={classes.readonlyEmptyMessage}>{props.emptyMessage}</p>
                        )}
                        {props.tokens.map((token, i) => {
                            return <React.Fragment key={i}>{token}</React.Fragment>;
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
