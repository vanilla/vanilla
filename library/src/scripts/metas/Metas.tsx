/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { metasClasses } from "@library/metas/Metas.styles";
import React, { ElementType } from "react";
import { cx } from "@emotion/css";
import classNames from "classnames";
import { Tag } from "@library/metas/Tags";
import { TagPreset, tagsVariables } from "@library/metas/Tags.variables";
import { Icon, IconSize, IconType } from "@vanilla/icons";
import { iconVariables } from "@library/icons/iconStyles";
import SmartLink from "@library/routing/links/SmartLink";
import ProfileLink from "@library/navigation/ProfileLink";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";

export interface IMetasProps extends React.HTMLAttributes<HTMLElement> {
    flex?: boolean;
    as?: ElementType;
}

export function Metas(props: IMetasProps) {
    const classes = metasClasses.useAsHook();
    // note: using cx to compose the class name breaks the sibling selectors required to ensure spacing between list item metas & description
    return <div {...props} className={cx(classes.root, props.className)} />;
}

export const MetaItem = React.forwardRef(function MetaItem(props: IMetasProps, ref: React.RefObject<HTMLDivElement>) {
    const classes = metasClasses.useAsHook();
    const { flex, as = "div", ...rest } = props;
    const Component = (as as "div") ?? "div";
    return <Component {...rest} ref={ref} className={cx(classes.meta, flex && classes.metaFlexed, props.className)} />;
});

export function MetaLink(props: React.ComponentProps<typeof SmartLink>) {
    const classes = metasClasses.useAsHook();

    const { className, ...linkProps } = props;
    return (
        <MetaItem className={props.className}>
            <SmartLink {...linkProps} className={classes.metaLink} />
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
    const classes = metasClasses.useAsHook();

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

export function MetaIcon(
    props: React.ComponentProps<typeof Icon> & {
        smallerThanContainer?: boolean;
    },
) {
    const { className, children, smallerThanContainer = false, ...rest } = props;
    const {
        standard: { height },
    } = iconVariables();

    const iconHeight = props.size ? IconSize[props.size] : height;
    const classes = metasClasses.useAsHook();

    return (
        <MetaItem className={className}>
            <Icon
                {...rest}
                className={classes.alignVerticallyInMetaItem(iconHeight, smallerThanContainer)}
                aria-hidden={false}
            />
            {children}
        </MetaItem>
    );
}

export function MetaButton(
    props: React.ButtonHTMLAttributes<HTMLButtonElement> & {
        icon?: IconType;
        buttonClassName?: string;
    },
) {
    const classes = metasClasses.useAsHook();

    const { icon, className, children, buttonClassName, ...buttonProps } = props;

    return (
        <MetaItem className={className}>
            <button
                {...buttonProps}
                className={cx(
                    {
                        [classes.iconButton]: !!icon,
                    },
                    buttonClassName,
                )}
            >
                {icon && (
                    <Icon
                        size={"default"}
                        icon={icon}
                        className={classes.alignVerticallyInMetaItem(iconVariables().standard.height)}
                    />
                )}
                {children}
            </button>
        </MetaItem>
    );
}

export function MetaProfile(props: { user: IUserFragment; className?: string; isUserCard?: boolean }) {
    const classes = metasClasses.useAsHook();

    return (
        <span className={cx(classes.profileMeta, props.className)}>
            <ProfileLink userFragment={props.user} isUserCard={props.isUserCard ?? true}>
                <UserPhoto size={UserPhotoSize.XSMALL} userInfo={props.user} />
                {props.user.name}
            </ProfileLink>
        </span>
    );
}
