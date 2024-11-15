/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import React from "react";

interface IProps {
    descriptionLines?: 1 | 2;
}

export function DashboardFormGroupPlaceholder(props: IProps) {
    const classes = dashboardFormGroupClasses();
    return (
        <div className={classes.formGroup}>
            <div className={classes.labelWrap}>
                <LoadingRectangle width="35%" height={14} />
                <LoadingSpacer height={6} />
                <LoadingRectangle width="80%" height={10} />
                {props.descriptionLines === 2 && (
                    <>
                        <LoadingSpacer height={4} />
                        <LoadingRectangle width="56%" height={10} />
                    </>
                )}
            </div>
            <DashboardInputWrap>
                <input className="form-control" disabled aria-hidden tabIndex={-1} style={{ background: "#fff" }} />
            </DashboardInputWrap>
        </div>
    );
}
