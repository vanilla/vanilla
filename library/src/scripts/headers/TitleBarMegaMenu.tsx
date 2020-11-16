/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useImperativeHandle, useLayoutEffect, useRef, useState } from "react";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import useClasses from "./TitleBarMegaMenu.styles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import Container from "@library/layout/components/Container";
import { titleBarNavigationVariables } from "@library/headers/titleBarNavStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { useLastValue, useMeasure } from "@vanilla/react-utils/src";
import { TabHandler } from "@vanilla/dom-utils/src";
import { containerVariables } from "@library/layout/components/containerStyles";

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
        <li className={className}>
            <a tabIndex={0} onKeyDown={onKeyDown} href={url}>
                {text}
            </a>
        </li>
    );
}

interface IProps {
    active?: INavigationVariableItem;
    logoDimensions?: DOMRect;
    currentNavElement?: HTMLElement;
    onOpen?(item: INavigationVariableItem): void;
    onClose?(item: INavigationVariableItem): void;
    forceMenuOpen?: INavigationVariableItem;
}

function TitleBarMegaMenuImpl(props: IProps, ref: React.Ref<IMegaMenuHandle>) {
    const { active, logoDimensions, onOpen, onClose, currentNavElement, forceMenuOpen } = props;

    const classes = useClasses();

    const { getCalcedHashOffset } = useScrollOffset();
    const [isFocusWithin, setIsFocusWithin] = useState(false);
    const [isMouseWithin, setIsMouseWithin] = useState(false);
    const [expanded, setExpanded] = useState<INavigationVariableItem | undefined>();
    const [shouldFocusFirstItem, setShouldFocusFirstItem] = useState(false);
    const [isHidden, setIsHidden] = useState(true);

    const hoveredHasChildren = active && active.children?.length;
    const isActive = forceMenuOpen || isFocusWithin || isMouseWithin;
    const isExpanded = expanded !== undefined;

    const titleBarRef = useRef<HTMLElement>(null);

    const measureTitleBar = useMeasure(titleBarRef, false, true);

    const menuHeight = measureTitleBar.height;
    const yBounds = getCalcedHashOffset() + menuHeight;

    useEffect(() => {
        if (expanded) {
            if (onOpen) onOpen(expanded);
            return () => {
                if (onClose) onClose(expanded);
            };
        }
    }, [expanded]);

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
            setShouldFocusFirstItem(true);
        },
    }));

    const focusFirstItem = useCallback(() => {
        if (shouldFocusFirstItem) {
            const firstItem = titleBarRef.current?.querySelector<HTMLElement>(`.${classes.menuItemChild} a`);
            if (firstItem) {
                firstItem?.focus();
                setShouldFocusFirstItem(false);
            }
        }
    }, [shouldFocusFirstItem, expanded, classes.menuItemChild, classes.menuItemTitle]);

    useLayoutEffect(() => {
        focusFirstItem();
    }, [focusFirstItem]);

    useEffect(() => {
        if (forceMenuOpen) {
            setExpanded(forceMenuOpen);
        } else if (hoveredHasChildren) {
            setExpanded(active);
        } else if (!isActive || (active && !active.children?.length)) {
            const timeout = setTimeout(() => setExpanded(undefined), HIDE_TIMEOUT_MS);
            return () => clearTimeout(timeout);
        }
    }, [active, isFocusWithin, hoveredHasChildren, isActive]);

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

    const containerPadding = containerVariables().spacing.padding * 2;

    const spacingLeft = logoDimensions ? logoDimensions.width + containerPadding : undefined;

    function handleKeyPress(event: React.KeyboardEvent) {
        if (titleBarRef.current === null || document.activeElement === null) {
            return;
        }

        const tabHandler = new TabHandler(titleBarRef.current);
        const nextElement = tabHandler.getNext(document.activeElement, false, false);
        const prevElement = tabHandler.getNext(document.activeElement, true, false);
        const parent = document.activeElement.closest('div[class^="titleBarMegaMenu-menuItem"]');
        const nextParentSibling = parent?.nextSibling as HTMLElement;
        const previousParentSibling = parent?.previousSibling as HTMLElement;

        switch (event.key) {
            case "Escape": {
                event.preventDefault();
                currentNavElement?.focus();
                setIsMouseWithin(false);
                break;
            }

            case "ArrowDown": {
                event.preventDefault();
                nextElement?.focus();
                setIsMouseWithin(false);
                break;
            }

            case "ArrowUp": {
                event.preventDefault();
                if (!prevElement) {
                    currentNavElement?.focus();
                } else {
                    prevElement?.focus();
                }

                setIsMouseWithin(false);

                break;
            }

            case "ArrowRight": {
                if (!nextParentSibling) {
                    return;
                }
                let parentSiblingFirstChild = nextParentSibling.querySelector("li a") as HTMLElement;
                parentSiblingFirstChild?.focus();
                break;
            }

            case "ArrowLeft": {
                if (!previousParentSibling) {
                    return;
                }
                let parentSiblingFirstChild = previousParentSibling.querySelector("li a") as HTMLElement;
                parentSiblingFirstChild?.focus();
                break;
            }

            case " ": {
                event.preventDefault();
                (event.target as HTMLElement).click();
            }
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
                ref={(ref) => {
                    (titleBarRef as any).current = ref;
                    focusFirstItem();
                }}
                style={{ paddingLeft: spacingLeft, width: `calc(100% - ${spacingLeft})` }}
                className={classes.container}
                fullGutter
            >
                {expanded?.children.map((item, key) => (
                    <div key={key} className={classes.menuItem}>
                        <>
                            {!item.children.length ? (
                                <TitleBarMegaMenuChild
                                    className={classes.menuItemChild}
                                    url={item.url}
                                    text={item.name}
                                    key={key}
                                    onKeyDown={(e) => handleKeyPress(e)}
                                />
                            ) : (
                                <span className={classes.menuItemTitle}>{item.name}</span>
                            )}

                            <ul>
                                {item.children.map((child, key) => (
                                    <TitleBarMegaMenuChild
                                        className={classes.menuItemChild}
                                        url={child.url}
                                        text={child.name}
                                        key={key}
                                        onKeyDown={(e) => handleKeyPress(e)}
                                    />
                                ))}
                            </ul>
                        </>
                    </div>
                ))}
            </Container>
        </div>
    );
}
export const TitleBarMegaMenuChild = React.forwardRef(TitleBarMegaMenuChildImpl);
export const TitleBarMegaMenu = React.forwardRef(TitleBarMegaMenuImpl);
