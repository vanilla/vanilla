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

interface IProps extends React.HTMLAttributes<HTMLSpanElement> {
    to?: LocationDescriptor;
    preset?: TagPreset;
}

export function Tag(props: IProps) {
    const { to, className, preset = TagPreset.STANDARD, ...rest } = props;
    const classes = tagClasses();

    if (to) {
        return <SmartLink {...rest} to={to} className={cx([classes[preset](true), className])}></SmartLink>;
    } else {
        return <span {...rest} className={cx(classes[preset](false), className)}></span>;
    }
}
