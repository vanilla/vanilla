/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useImperativeHandle, useRef, useState } from "react";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import useClasses, { titleBarMegaMenuVariables } from "./TitleBarMegaMenu.styles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import Container from "@library/layout/components/Container";
import { useMeasure } from "@vanilla/react-utils/src";
import { TabHandler } from "@vanilla/dom-utils/src";
import SmartLink from "@library/routing/links/SmartLink";
import { globalVariables } from "@library/styles/globalStyleVars";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useHistory } from "react-router";

/** How much time is elapsed before the menu is hidden, either from loss of focus or mouseout. */
const HIDE_TIMEOUT_MS = 250;

export interface IMegaMenuHandle {
    focusFirstItem();
}

export interface IMegaMenuChildHandle {}

interface IChildProps {
    text: string;
    url: string;
    className: string;
    onKeyDown: any;
}

function TitleBarMegaMenuChildImpl(props: IChildProps, ref: React.Ref<IMegaMenuChildHandle>) {
    const { className, url, text, onKeyDown } = props;

    return (
        <li className={className} role="menuitem">
            <SmartLink tabIndex={0} onKeyDown={onKeyDown} to={url}>
                {text}
            </SmartLink>
        </li>
    );
}
interface IProps {
    expanded?: INavigationVariableItem;
    leftOffset?: number;
    menuItem?: HTMLElement;
    onClose?(): void;
}

function TitleBarMegaMenuImpl(props: IProps, ref: React.Ref<IMegaMenuHandle>) {
    const { expanded, leftOffset = 0, onClose, menuItem } = props;
    const { hasPermission } = usePermissionsContext();

    const classes = useClasses();

    const { getCalcedHashOffset } = useScrollOffset();
    const [isFocusWithin, setIsFocusWithin] = useState(false);
    const [isMouseWithin, setIsMouseWithin] = useState(false);
    const [isHidden, setIsHidden] = useState(true);

    const onCloseRef = useRef<(() => void) | undefined>(onClose);

    const isExpanded = !!expanded && !!expanded.children?.length;
    const isActive = isFocusWithin || isMouseWithin;

    const containerRef = useRef<HTMLElement>(null);

    const containerDimensions = useMeasure(containerRef, false, true);

    const menuHeight = containerDimensions.height;
    const yBounds = getCalcedHashOffset() + menuHeight;
    const itemsWithNoChildren: INavigationVariableItem[] = [];

    //pulling out items with no children to group them together in separate menuItem
    expanded?.children?.map((item) => {
        if (!item.children?.length) {
            itemsWithNoChildren.push(item);
        }
    });

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        if (isExpanded) {
            setIsHidden(false);
        } else {
            const timeout = setTimeout(() => {
                setIsHidden(true);
            }, 200);
            return () => clearTimeout(timeout);
        }
    }, [isExpanded]);

    useImperativeHandle(ref, () => ({
        focusFirstItem: () => {
            const firstItem = containerRef.current?.querySelector<HTMLElement>(`.${classes.menuItemChild} a`);
            firstItem?.focus();
        },
    }));

    useEffect(() => {
        if (!isActive) {
            const onCloseCallback = onCloseRef.current;
            const timeout = setTimeout(() => {
                if (onCloseCallback) onCloseCallback();
            }, HIDE_TIMEOUT_MS);
            return () => clearTimeout(timeout);
        }
    }, [isActive]);

    useEffect(() => {
        function onMouseMove(event: MouseEvent) {
            setIsMouseWithin(event.clientY <= yBounds);
        }
        window.addEventListener("mousemove", onMouseMove);
        return () => {
            window.removeEventListener("mousemove", onMouseMove);
        };
    }, [yBounds]);

    useEffect(() => {
        // Reset mouse state whenever focus changes so we
        // don't rely on the pointer when using the keyboard.
        setIsMouseWithin(false);
    }, [expanded]);

    function handleKeyPress(event: React.KeyboardEvent) {
        if (containerRef.current === null) {
            return;
        }

        const tabHandler = new TabHandler(containerRef.current);
        const target = event.target as HTMLElement;
        const nextElement = tabHandler.getNext(target, false, false);
        const prevElement = tabHandler.getNext(target, true, false);

        switch (event.key) {
            case "Escape": {
                event.preventDefault();
                menuItem?.focus();
                setIsMouseWithin(false);
                break;
            }
            case "ArrowRight":
            case "ArrowLeft":
                // No prevent default.
                menuItem?.focus();
                setIsMouseWithin(false);
                menuItem?.dispatchEvent(new KeyboardEvent("keydown", { key: event.key, bubbles: true }));
                break;

            case "ArrowDown": {
                event.preventDefault();
                nextElement?.focus();
                setIsMouseWithin(false);
                break;
            }

            case "ArrowUp": {
                event.preventDefault();

                if (!prevElement) {
                    menuItem?.focus();
                } else {
                    prevElement?.focus();
                }

                break;
            }

            case " ": {
                event.preventDefault();
                (event.target as HTMLElement).click();
            }
        }
    }

    function generateMegaMenuList(items: INavigationVariableItem[]) {
        return (
            <ul className={classes.menuItemChildren} role="menu">
                {items.map((item, key) => {
                    if (item.permission && !hasPermission(item.permission)) {
                        return <React.Fragment key={key}></React.Fragment>;
                    }
                    return (
                        <TitleBarMegaMenuChild
                            className={classes.menuItemChild}
                            url={item.url}
                            text={item.name}
                            key={key}
                            onKeyDown={(e) => handleKeyPress(e)}
                        />
                    );
                })}
            </ul>
        );
    }

    const megaMenuVars = titleBarMegaMenuVariables();

    function calculateContainerOffset() {
        switch (megaMenuVars.wrapper.alignment) {
            case "firstItem":
                const firstItemOffset = leftOffset;
                const containerOffset = containerDimensions.left;
                // With logo alignment we take the offset of the logo
                // and remove our own containers offset to get the difference
                // Example:
                // ------------------------------------------------------
                // |  (space)                 LOGO     Item1    Item2
                // |  (space)                          subitem     subitem
                // |  (space)                          subitem     subitem
                // ------------------------------------------------------
                // |-- firstItemOffset ---------------|
                // |-- containerOffset --|
                //                       |-- RESULT --|
                return firstItemOffset - containerOffset;
            case "logo":
                // Align with the logo
                // Example:
                // ------------------------------------------------------
                // |       LOGO     Item1    Item2
                // |       subitem     subitem
                // |       subitem     subitem
                // ------------------------------------------------------
                // |------| fullGutter
                //        |-| item space
                return globalVariables().constants.fullGutter - titleBarMegaMenuVariables().item.spacer;
        }
    }

    return (
        <div
            className={classes.wrapper}
            style={{
                top: getCalcedHashOffset(),
                height: isExpanded ? menuHeight : 0,
                display: isHidden ? "none" : undefined,
            }}
            onFocus={() => setIsFocusWithin(true)}
            onBlur={() => setIsFocusWithin(false)}
        >
            <Container
                ref={containerRef}
                style={{
                    paddingLeft: calculateContainerOffset(),
                }}
                className={classes.container}
                ignoreContext
                fullGutter
            >
                {expanded?.children?.map((item, key) =>
                    item.children?.length ? (
                        <div key={key} className={classes.menuItem}>
                            <span className={classes.menuItemTitle} role="heading">
                                {item.name}
                            </span>
                            {item.children && generateMegaMenuList(item.children)}
                        </div>
                    ) : (
                        <React.Fragment key={key} />
                    ),
                )}
                {itemsWithNoChildren.length ? (
                    <div className={classes.menuItem}>{generateMegaMenuList(itemsWithNoChildren)}</div>
                ) : (
                    <React.Fragment />
                )}
            </Container>
        </div>
    );
}
export const TitleBarMegaMenuChild = React.forwardRef(TitleBarMegaMenuChildImpl);
export const TitleBarMegaMenu = React.forwardRef(TitleBarMegaMenuImpl);
