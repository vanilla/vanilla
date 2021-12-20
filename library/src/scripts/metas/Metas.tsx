/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { metasClasses } from "@library/metas/Metas.styles";
import React from "react";
import { cx } from "@emotion/css";
import classNames from "classnames";
import { Tag } from "@library/metas/Tags";
import { TagPreset, tagsVariables } from "@library/metas/Tags.variables";
import { Icon } from "@vanilla/icons";
import { iconVariables } from "@library/icons/iconStyles";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

export function Metas(props: IProps) {
    const classes = metasClasses();
    // note: using cx to compose the class name breaks the sibling selectors required to ensure spacing between list item metas & description
    return <div {...props} className={classNames(classes.root, props.className)} />;
}

export const MetaItem = React.forwardRef(function MetaItem(props: IProps, ref: React.RefObject<HTMLDivElement>) {
    const classes = metasClasses();
    return <div {...props} ref={ref} className={cx(classes.meta, props.className)} />;
});

export function MetaLink(props: React.ComponentProps<typeof SmartLink>) {
    const classes = metasClasses();

    return (
        <MetaItem>
            <SmartLink {...props} className={classNames(classes.metaLink, props.className)} />
        </MetaItem>
    );
}

export const MetaTag = React.forwardRef(function MetaTag(
    props: {
        tagPreset?: TagPreset;
    } & React.ComponentProps<typeof Tag>,
    ref: React.RefObject<HTMLDivElement>,
) {
    const { tagPreset, ...rest } = props;
    const { height } = tagsVariables();
    const classes = metasClasses();

    return (
        <MetaItem ref={ref}>
            <Tag
                {...rest}
                preset={props.tagPreset}
                className={cx(classes.alignVerticallyInMetaItem(height), props.className)}
            />
        </MetaItem>
    );
});

export function MetaIcon(props: React.ComponentProps<typeof Icon>) {
    const { className, children, ...rest } = props;
    const {
        standard: { height: iconHeight },
    } = iconVariables();
    const classes = metasClasses();

    return (
        <MetaItem className={className}>
            <Icon {...rest} className={classes.alignVerticallyInMetaItem(iconHeight)} /> {children}
        </MetaItem>
    );
}
