/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { getButtonStyleFromBaseClass } from "@library/forms/Button";
import classNames from "classnames";
import TitleBarListItem from "@library/headers/mebox/pieces/TitleBarListItem";

export interface ITitleBarNav extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
    className?: string;
    to: string;
    isActive?: boolean;
    children: React.ReactNode;
    linkClassName?: string;
    linkContentClassName?: string;
    buttonType?: ButtonTypes;
    permission?: string;
}

interface IProps extends ITitleBarNav {}

/**
 * Implements Navigation item component for header
 */
export const TitleBarNavItem = React.forwardRef(function TitleBarNavItem(
    props: IProps,
    ref: React.Ref<HTMLAnchorElement>,
) {
    const {
        isActive,
        to,
        className,
        buttonType,
        linkClassName,
        linkContentClassName,
        children,
        permission,
        ...passthru
    } = props;

    const classes = titleBarNavClasses();

    return (
        <TitleBarListItem className={classNames(className, classes.root, { isActive })}>
            <SmartLink
                {...passthru}
                ref={ref}
                to={to}
                className={classNames(
                    linkClassName,
                    classes.link,
                    buttonType ? getButtonStyleFromBaseClass(buttonType) : "",
                )}
            >
                <div
                    className={classNames({
                        linkContentClassName: true,
                        [classes.linkActive]: isActive,
                    })}
                >
                    {children}
                </div>
            </SmartLink>
        </TitleBarListItem>
    );
});
