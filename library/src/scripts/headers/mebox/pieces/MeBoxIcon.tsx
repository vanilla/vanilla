/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Count from "@library/content/Count";
import { compactMeBoxClasses } from "@library/headers/mebox/pieces/compactMeBoxStyles";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import React from "react";
import classNames from "classnames";

/**
 * UI for a MeBox button.
 */
export function MeBoxIcon(props: IProps) {
    const { count, countLabel, compact, children } = props;
    const compactClasses = compactMeBoxClasses();
    const classes = meBoxClasses();
    return (
        <div
            className={classNames(
                "meBox-buttonContent",
                compact ? compactClasses.tabButtonContent : classes.buttonContent,
            )}
        >
            {children}
            {count != null && countLabel && count > 0 && <Count label={countLabel} count={count} />}
        </div>
    );
}

interface IProps {
    compact: boolean;
    children: React.ReactNode;
    count?: number;
    countLabel?: string;
}
