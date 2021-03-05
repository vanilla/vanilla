/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { metasClasses } from "@library/metas/Metas.styles";
import React from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

export function Metas(props: IProps) {
    const classes = metasClasses();
    return <div {...props} className={cx(classes.root, props.className)} />;
}

export function MetaItem(props: IProps) {
    const classes = metasClasses();
    return <div {...props} className={cx(classes.meta, props.className)} />;
}

export function MetaIcon(props: IProps) {
    const classes = metasClasses();
    return <div {...props} className={cx(classes.metaIcon, props.className)} />;
}
