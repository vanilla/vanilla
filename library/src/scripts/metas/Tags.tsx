/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import SmartLink from "@library/routing/links/SmartLink";
import React from "react";
import { LocationDescriptor } from "history";
import { cx } from "@emotion/css";
import { tagClasses } from "@library/metas/Tags.styles";
import { TagPreset } from "@library/metas/Tags.variables";
import { ToolTip } from "@library/toolTip/ToolTip";

export interface ITagProps extends React.HTMLAttributes<HTMLSpanElement> {
    to?: LocationDescriptor;
    preset?: TagPreset;
    tooltipLabel?: string;
}

export function Tag(props: ITagProps) {
    const { to, className, color, preset = TagPreset.STANDARD, tooltipLabel, ...rest } = props;
    const classes = tagClasses();

    let result: React.ReactNode;
    if (to) {
        result = <SmartLink {...rest} to={to} className={cx([classes[preset](true), className])}></SmartLink>;
    } else {
        result = <span {...rest} className={cx(classes[preset](false), className)}></span>;
    }

    if (tooltipLabel) {
        result = (
            <ToolTip label={tooltipLabel}>
                <span>{result}</span>
            </ToolTip>
        );
    }
    return <>{result}</>;
}
