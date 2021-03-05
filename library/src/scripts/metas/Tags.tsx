/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import SmartLink from "@library/routing/links/SmartLink";
import React from "react";
import { LocationDescriptor } from "history";
import { cx } from "@emotion/css";
import { metasClasses } from "@library/metas/Metas.styles";

interface IProps extends React.HTMLAttributes<HTMLSpanElement> {
    to?: LocationDescriptor;
}

export function Tag(_props: IProps) {
    const { to, className, ...props } = _props;
    const classes = metasClasses();
    const componentClasses = cx(classes.metaLabel, className);

    if (to) {
        return <SmartLink {...props} to={to} className={componentClasses}></SmartLink>;
    } else {
        return <span {...props} className={componentClasses}></span>;
    }
}
