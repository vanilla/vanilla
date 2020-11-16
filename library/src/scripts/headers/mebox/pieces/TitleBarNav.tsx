/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import classNames from "classnames";
import TitleBarNavItem from "@library/headers/mebox/pieces/TitleBarNavItem";
import Permission from "@library/features/users/Permission";
import { INavigationVariableItem, navigationVariables } from "@library/headers/navigationVariables";
import FlexSpacer from "@library/layout/FlexSpacer";
import { IMegaMenuHandle, TitleBarMegaMenu } from "@library/headers/TitleBarMegaMenu";

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
    logoDimensions?: DOMRect;
    forceMenuOpen?: INavigationVariableItem; // For storybook, will force nested menu open
}

/**
 * Implements Navigation component for header
 */
export default function TitleBarNav(props: ITitleBarNavProps) {
    const { navigationItems } = navigationVariables();
    const [active, setActive] = useState<INavigationVariableItem>();
    const [currentNavElement, setCurrentNavElement] = useState<HTMLElement>();
    const [expanded, setExpanded] = useState<INavigationVariableItem>();

    const megaMenuRef = useRef<IMegaMenuHandle>(null);

    const classes = titleBarNavClasses();
    const dataLength = Object.keys(navigationItems).length;
    const content = navigationItems.map((item, key) => {
        if (item.isHidden) {
            return <React.Fragment key={key}></React.Fragment>;
        }

        function onActive(element) {
            setActive(item);
            setCurrentNavElement(element);
        }

        function onMouseOutOrBlur() {
            setActive(undefined);
        }

        function onKeyDown(event: React.KeyboardEvent) {
            switch (event.key) {
                case "Enter":
                case " ":
                case "ArrowDown":
                    event.preventDefault();
                    onActive(event.target);
                    megaMenuRef.current?.focusFirstItem();
                    break;
            }
        }

        const component = (
            <TitleBarNavItem
                to={item.url}
                className={classNames({
                    [classes.lastItem]: dataLength === key,
                    [classes.firstItem]: key === 0,
                    [classes.linkActive]: expanded === item,
                })}
                onMouseOver={onActive}
                onMouseOut={onMouseOutOrBlur}
                onBlur={onMouseOutOrBlur}
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
                    <>
                        {props.excludeExtraNavItems ??
                            TitleBarNav.extraNavItems.map((ComponentClass, i) => {
                                return <ComponentClass key={i} />;
                            })}
                    </>
                </ul>
                {props.afterNode}
            </div>
            <TitleBarMegaMenu
                forceMenuOpen={props.forceMenuOpen}
                ref={megaMenuRef}
                logoDimensions={props.logoDimensions}
                active={active}
                onOpen={(item) => setExpanded(item)}
                onClose={() => setExpanded(undefined)}
                currentNavElement={currentNavElement}
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
