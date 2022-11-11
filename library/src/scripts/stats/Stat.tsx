/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentProps, ReactNode } from "react";
import { statClasses } from "./Stat.styles";
import { cx } from "@emotion/css";
import SmartLink from "@library/routing/links/SmartLink";
import NumberFormatted from "@library/content/NumberFormatted";
import { RecordID, labelize } from "@vanilla/utils";

export function Stat(props: {
    to?: ComponentProps<typeof SmartLink>["to"];
    label: string;
    value: RecordID | ReactNode;
    classNames?: string;
}) {
    const classes = statClasses();
    const { to, value, label, classNames } = props;

    const content = (
        <>
            <div className={classes.statData}>
                {typeof value === "number" ? (
                    <NumberFormatted fallbackTag={"div"} value={value || 0} title={label} />
                ) : (
                    value
                )}
            </div>
            <label className={classes.statLabel}>{labelize(label)}</label>
        </>
    );

    return to ? (
        <SmartLink title={label} to={to} className={cx(classes.statItem, classes.statItemLink, classNames)}>
            {content}
        </SmartLink>
    ) : (
        <div className={cx(classes.statItem, classNames)}>{content}</div>
    );
}
