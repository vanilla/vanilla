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
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { useLastValue, useMeasure } from "@vanilla/react-utils/src";
import { TabHandler } from "@vanilla/dom-utils/src";
import { containerVariables } from "@library/layout/components/containerStyles";
import { Func } from "mocha";
import SmartLink from "@library/routing/links/SmartLink";

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

    const classes = useClasses();

    const { getCalcedHashOffset } = useScrollOffset();
    const [isFocusWithin, setIsFocusWithin] = useState(false);
    const [isMouseWithin, setIsMouseWithin] = useState(false);
    const [shouldFocusFirstItem, setShouldFocusFirstItem] = useState(false);
    const [isHidden, setIsHidden] = useState(true);

    const onCloseRef = useRef<(() => void) | undefined>(onClose);

    const isExpanded = expanded && expanded.children?.length;
    const isActive = isFocusWithin || isMouseWithin;

    const containerRef = useRef<HTMLElement>(null);

    const containerDimensions = useMeasure(containerRef, false, true);

    const menuHeight = containerDimensions.height;
    const yBounds = getCalcedHashOffset() + menuHeight;

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
            setShouldFocusFirstItem(true);
        },
    }));

    const focusFirstItem = useCallback(() => {
        if (shouldFocusFirstItem) {
            const firstItem = containerRef.current?.querySelector<HTMLElement>(`.${classes.menuItemChild} a`);
            if (firstItem) {
                firstItem?.focus();
                setShouldFocusFirstItem(false);
            }
        }
    }, [shouldFocusFirstItem, classes.menuItemChild]);

    useLayoutEffect(() => {
        focusFirstItem();
    }, [focusFirstItem]);

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
        if (containerRef.current === null || document.activeElement === null) {
            return;
        }

        const tabHandler = new TabHandler(containerRef.current);
        const target = event.target as HTMLElement;
        const nextElement = tabHandler.getNext(target, false, false);
        const prevElement = tabHandler.getNext(target, true, false);
        const parent = target.closest('div[class^="titleBarMegaMenu-menuItem"]');
        const nextParentSibling = parent?.nextSibling as HTMLElement;
        const previousParentSibling = parent?.previousSibling as HTMLElement;

        switch (event.key) {
            case "Escape": {
                event.preventDefault();
                menuItem?.focus();
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
                    menuItem?.focus();
                } else {
                    prevElement?.focus();
                }

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
                    (containerRef as any).current = ref;
                    focusFirstItem();
                }}
                style={{ paddingLeft: leftOffset - containerDimensions.left }}
                className={classes.container}
                fullGutter
            >
                {expanded?.children?.map((item, key) => (
                    <div key={key} className={classes.menuItem}>
                        {!item.children?.length ? (
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

                        {item.children && (
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
                        )}
                    </div>
                ))}
                {/* These items ensure that empty space in the row is filled */}
                {[...Array(6).keys()].map((key) => (
                    <span key={key} className={classes.fillerItem} />
                ))}
            </Container>
        </div>
    );
}
export const TitleBarMegaMenuChild = React.forwardRef(TitleBarMegaMenuChildImpl);
export const TitleBarMegaMenu = React.forwardRef(TitleBarMegaMenuImpl);
