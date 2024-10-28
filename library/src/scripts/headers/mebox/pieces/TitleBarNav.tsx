/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import classNames from "classnames";
import { TitleBarNavItem } from "@library/headers/mebox/pieces/TitleBarNavItem";
import { INavigationVariableItem, navigationVariables } from "@library/headers/navigationVariables";
import FlexSpacer from "@library/layout/FlexSpacer";
import { IMegaMenuHandle, TitleBarMegaMenu } from "@library/headers/TitleBarMegaMenu";
import { formatUrl, siteUrl, t } from "@library/utility/appUtils";
import { useHistory, useLocation } from "react-router";
import { useMeasure } from "@vanilla/react-utils";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

export interface ITitleBarNavProps {
    className?: string;
    linkClassName?: string;
    listClassName?: string;
    children?: React.ReactNode;
    wrapper?: JSX.Element;
    excludeExtraNavItems?: boolean;
    containerRef?: React.RefObject<HTMLElement | null>;
    isCentered?: boolean;
    afterNode?: React.ReactNode;
    // For storybook.
    forceOpen?: boolean;
    navigationItems?: INavigationVariableItem[];
}

/**
 * Implements Navigation component for header
 */
export default function TitleBarNav(props: ITitleBarNavProps) {
    const navigationItems = props.navigationItems ?? navigationVariables().navigationItems;

    const location = useLocation();

    const [menuItem, setMenuItem] = useState<HTMLElement>();
    const [expanded, setExpanded] = useState<INavigationVariableItem>();

    // If the location changes we close the menu.
    const history = useHistory();
    useEffect(() => {
        const unlisten = history.listen(() => {
            setMenuItem(undefined);
            setExpanded(undefined);
        });
        return unlisten;
    }, [history, setMenuItem, setExpanded]);

    const megaMenuRef = useRef<IMegaMenuHandle>(null);
    const firstItemRef = useRef<HTMLAnchorElement | HTMLButtonElement>();

    const active = !!expanded && !!expanded.children?.length && expanded;

    const firstItemDimensions = useMeasure(firstItemRef as any, false, true);
    const { hasPermission } = usePermissionsContext();

    const classes = titleBarNavClasses();
    let haveActiveNavItem = false;
    const filteredNavItems = navigationItems.filter((item) => {
        if (item.isHidden) {
            return false;
        }
        if (!item.permission) {
            return true;
        } else {
            return hasPermission(item.permission);
        }
    });

    const dataLength = Object.keys(filteredNavItems).length;

    const content = filteredNavItems.map((item, key) => {
        function onActive(element) {
            setExpanded(item);
            setMenuItem(element);
        }

        function onFocus(element) {
            if (expanded !== item) {
                setExpanded(undefined);
            }

            if (item.children && item.children.length > 0) {
                onActive(element);
            }
        }

        function onKeyDown(event: React.KeyboardEvent<HTMLElement>) {
            const target = event.target as HTMLElement;
            const listItem = target.closest("li");
            const nextSibling = listItem?.nextSibling as HTMLElement;
            const previousSibling = listItem?.previousSibling as HTMLElement;
            switch (event.key) {
                case "Enter":
                case " ":
                    event.preventDefault();
                    if (item.children && item.children.length > 0) {
                        onActive(event.target);
                        setTimeout(() => {
                            megaMenuRef.current?.focusFirstItem();
                        });
                    } else {
                        (event.target as HTMLElement).click();
                    }
                    break;
                case "Escape":
                    event.preventDefault();
                    setExpanded(undefined);
                    setMenuItem(undefined);
                    break;
                case "ArrowDown":
                    event.preventDefault();
                    onActive(event.target);
                    setTimeout(() => {
                        megaMenuRef.current?.focusFirstItem();
                    });
                    break;
                case "ArrowUp":
                    event.preventDefault();
                    setExpanded(undefined);
                    break;
                case "ArrowRight":
                    event.preventDefault();
                    nextSibling?.querySelector<HTMLAnchorElement>("a")?.focus();
                    break;
                case "ArrowLeft":
                    event.preventDefault();
                    previousSibling?.querySelector<HTMLAnchorElement>("a")?.focus();
                    break;
            }
        }

        /**
         * Checks if we're on the current page
         * Note that won't work with non-canonical URLHeaderLogo.tsx
         */
        const isCurrentPage = (): boolean => {
            if (location && location.pathname) {
                return siteUrl(window.location.pathname) === formatUrl(item.url, true);
            } else {
                return false;
            }
        };

        //if we already have active navItem, avoid duplicates (cases when same url for multiple items etc)
        const navItemIsActive = (active ? active === item : isCurrentPage()) && !haveActiveNavItem;
        if (navItemIsActive) {
            haveActiveNavItem = true;
        }

        return (
            <TitleBarNavItem
                id={item.id}
                to={item.url}
                ref={(ref) => {
                    if (key === 0) firstItemRef.current = ref!;
                }}
                isActive={navItemIsActive}
                className={classNames({
                    [classes.lastItem]: dataLength === key,
                    [classes.firstItem]: key === 0,
                })}
                onMouseEnter={(event) => onActive(event.target)}
                onFocus={(event) => onFocus(event.target)}
                onKeyDown={onKeyDown}
                linkClassName={props.linkClassName}
                key={key}
                hasPopupMenu={item.children && !!item.children.length}
            >
                {t(item.name)}
            </TitleBarNavItem>
        );
    });

    return (
        <>
            {props.isCentered && <FlexSpacer actualSpacer />}
            <div
                ref={props.containerRef as any}
                className={classNames(
                    "headerNavigation",
                    props.className,
                    classes.navigation,
                    props.isCentered && classes.navigationCentered,
                )}
            >
                <ul className={classNames(props.listClassName, classes.items)}>
                    {props.children ? props.children : content}
                    {props.excludeExtraNavItems ??
                        TitleBarNav.extraNavItems.map((ComponentClass, i) => {
                            return <ComponentClass key={i} />;
                        })}
                </ul>
                {props.afterNode}
            </div>
            <TitleBarMegaMenu
                ref={megaMenuRef}
                leftOffset={firstItemDimensions.left}
                menuItem={menuItem}
                expanded={props.forceOpen ? filteredNavItems[0] : expanded}
                onClose={props.forceOpen ? () => {} : () => setExpanded(undefined)}
            />
        </>
    );
}

/**
 * Additional items to render in the navigation.
 */
TitleBarNav.extraNavItems = [] as React.ComponentType[];

/**
 * Add some extra class.
 *
 * @param componentClass A react component class/function
 */
TitleBarNav.addNavItem = (componentClass: React.ComponentType) => {
    TitleBarNav.extraNavItems.push(componentClass);
};
