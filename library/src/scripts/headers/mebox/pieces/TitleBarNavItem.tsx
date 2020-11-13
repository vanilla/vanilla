/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { AnchorHTMLAttributes } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import SmartLink, { ISmartLinkProps } from "@library/routing/links/SmartLink";
import { getButtonStyleFromBaseClass } from "@library/forms/Button";
import { useLocation } from "react-router";
import classNames from "classnames";
import TitleBarListItem from "@library/headers/mebox/pieces/TitleBarListItem";
import { formatUrl, siteUrl } from "@library/utility/appUtils";

export interface ITitleBarNav extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
    className?: string;
    to: string;
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
export function TitleBarNavItem(props: IProps) {
    const location = useLocation();
    const { to, className, buttonType, linkClassName, linkContentClassName, children, permission, ...passthru } = props;

    /**
     * Checks if we're on the current page
     * Note that won't work with non-canonical URLHeaderLogo.tsx
     */
    const currentPage = (): boolean => {
        if (location && location.pathname) {
            return siteUrl(window.location.pathname) === formatUrl(to, true);
        } else {
            return false;
        }
    };
    const isCurrent = currentPage();
    const classes = titleBarNavClasses();

    return (
        <TitleBarListItem className={classNames(className, classes.root, { isCurrent })}>
            <SmartLink
                {...passthru}
                to={to}
                className={classNames(
                    linkClassName,
                    classes.link,
                    buttonType ? getButtonStyleFromBaseClass(buttonType) : "",
                )}
            >
                <div className={classNames(linkContentClassName, isCurrent ? classes.linkActive : "")}>{children}</div>
            </SmartLink>
        </TitleBarListItem>
    );
}

export default TitleBarNavItem;
