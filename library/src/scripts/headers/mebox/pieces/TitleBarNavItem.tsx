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
import { useLocation } from "react-router";
import classNames from "classnames";
import TitleBarListItem from "@library/headers/mebox/pieces/TitleBarListItem";
import { formatUrl, siteUrl } from "@library/utility/appUtils";

export interface ITitleBarNav {
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

    /**
     * Checks if we're on the current page
     * Note that won't work with non-canonical URLHeaderLogo.tsx
     */
    const currentPage = (): boolean => {
        if (location && location.pathname) {
            return siteUrl(window.location.pathname) === formatUrl(props.to, true);
        } else {
            return false;
        }
    };
    const isCurrent = currentPage();
    const classes = titleBarNavClasses();

    return (
        <TitleBarListItem className={classNames(props.className, classes.root, { isCurrent })}>
            <SmartLink
                to={props.to}
                className={classNames(
                    props.linkClassName,
                    classes.link,
                    props.buttonType ? getButtonStyleFromBaseClass(props.buttonType) : "",
                )}
            >
                <div className={classNames(props.linkContentClassName, isCurrent ? classes.linkActive : "")}>
                    {props.children}
                </div>
            </SmartLink>
        </TitleBarListItem>
    );
}

export default TitleBarNavItem;
