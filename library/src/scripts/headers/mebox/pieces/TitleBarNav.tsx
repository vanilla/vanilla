/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import classNames from "classnames";
import { TitleBarNavItem } from "@library/headers/mebox/pieces/TitleBarNavItem";
import Permission from "@library/features/users/Permission";
import { INavigationVariableItem, navigationVariables } from "@library/headers/navigationVariables";
import FlexSpacer from "@library/layout/FlexSpacer";
import { IMegaMenuHandle, TitleBarMegaMenu } from "@library/headers/TitleBarMegaMenu";
import { formatUrl, siteUrl } from "@library/utility/appUtils";
import { useLocation } from "react-router";
import { useMeasure } from "@vanilla/react-utils";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";

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
    forceMenuOpen?: INavigationVariableItem; // For storybook, will force nested menu open
}

/**
 * Implements Navigation component for header
 */
export default function TitleBarNav(props: ITitleBarNavProps) {
    const { navigationItems } = navigationVariables();

    const location = useLocation();

    const [menuItem, setMenuItem] = useState<HTMLElement>();
    const [expanded, setExpanded] = useState<INavigationVariableItem>();

    const megaMenuRef = useRef<IMegaMenuHandle>(null);
    const firstItemRef = useRef<HTMLAnchorElement>();

    const active = expanded && expanded.children?.length && expanded;

    const firstItemDimensions = useMeasure(firstItemRef as any);

    const classes = titleBarNavClasses();
    const dataLength = Object.keys(navigationItems).length;
    const content = navigationItems.map((item, key) => {
        if (item.isHidden) {
            return <React.Fragment key={key}></React.Fragment>;
        }

        function onActive(element) {
            setExpanded(item);
            setMenuItem(element);
        }

        function onFocus(element) {
            if (expanded !== item) setExpanded(undefined);
        }

        function onKeyDown(event: React.KeyboardEvent<HTMLElement>) {
            const target = event.target as HTMLElement;
            const listItem = target.closest("li");
            const nextSibling = listItem?.nextSibling as HTMLElement;
            const previousSibling = listItem?.previousSibling as HTMLElement;
            switch (event.key) {
                case "Enter":
                case " ":
                case "ArrowDown":
                    event.preventDefault();
                    onActive(event.target);
                    megaMenuRef.current?.focusFirstItem();
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

        const component = (
            <TitleBarNavItem
                to={item.url}
                ref={(ref) => {
                    if (key === 0) firstItemRef.current = ref!;
                }}
                isActive={active ? active === item : isCurrentPage()}
                className={classNames({
                    [classes.lastItem]: dataLength === key,
                    [classes.firstItem]: key === 0,
                })}
                onMouseEnter={(event) => onActive(event.target)}
                onFocus={(event) => onFocus(event.target)}
                onKeyDown={onKeyDown}
                linkClassName={props.linkClassName}
                key={key}
            >
                {item.name}
            </TitleBarNavItem>
        );

        if (item.permission) {
            return (
                <Permission key={key} permission={item.permission}>
                    {component}
                </Permission>
            );
        } else {
            return component;
        }
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
                expanded={expanded}
                onClose={() => setExpanded(undefined)}
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
