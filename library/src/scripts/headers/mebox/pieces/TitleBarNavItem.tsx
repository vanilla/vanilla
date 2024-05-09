/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import SmartLink from "@library/routing/links/SmartLink";
import Button, { getClassForButtonType } from "@library/forms/Button";
import classNames from "classnames";
import TitleBarListItem from "@library/headers/mebox/pieces/TitleBarListItem";

export interface ITitleBarNav extends React.AnchorHTMLAttributes<HTMLAnchorElement | HTMLButtonElement> {
    className?: string;
    to: string;
    isActive?: boolean;
    children: React.ReactNode;
    linkClassName?: string;
    linkContentClassName?: string;
    buttonType?: ButtonTypes;
    permission?: string;
}

interface IProps extends ITitleBarNav {
    hasPopupMenu?: boolean;
}

/**
 * Implements Navigation item component for header
 */
export const TitleBarNavItem = React.forwardRef(function TitleBarNavItem(
    props: IProps,
    ref: React.Ref<HTMLAnchorElement | HTMLButtonElement>,
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
        hasPopupMenu,
        ...passthru
    } = props;

    const classes = titleBarNavClasses();
    const content = (
        <div
            className={classNames({
                linkContentClassName: true,
                [classes.linkActive]: isActive,
            })}
        >
            {children}
        </div>
    );

    return (
        <TitleBarListItem className={classNames(className, classes.root, { isActive })}>
            {to && (
                <SmartLink
                    {...passthru}
                    ref={ref as unknown as React.Ref<HTMLAnchorElement>}
                    to={to}
                    className={classNames(
                        linkClassName,
                        classes.link,
                        buttonType ? getClassForButtonType(buttonType) : "",
                    )}
                    role="menulink"
                    aria-haspopup={hasPopupMenu}
                    aria-expanded={(hasPopupMenu && isActive) ?? undefined}
                >
                    {content}
                </SmartLink>
            )}
            {!to && (
                <Button
                    buttonRef={ref as unknown as React.Ref<HTMLButtonElement>}
                    onFocus={passthru.onFocus}
                    onMouseEnter={passthru.onMouseEnter}
                    onKeyDown={passthru.onKeyDown}
                    buttonType={ButtonTypes.TEXT}
                    role="menubutton"
                    className={classNames(
                        classes.navLinkAsButton,
                        linkClassName,
                        classes.link,
                        buttonType ? getClassForButtonType(buttonType) : "",
                    )}
                    aria-haspopup={hasPopupMenu}
                    aria-expanded={(hasPopupMenu && isActive) ?? undefined}
                >
                    {content}
                </Button>
            )}
        </TitleBarListItem>
    );
});
