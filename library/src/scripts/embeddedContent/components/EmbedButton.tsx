/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button, { IButtonProps } from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import classNames from "classnames";
import React, { PropsWithChildren, useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";
import { embedButtonClasses } from "./embedButtonStyles";

interface IProps extends IButtonProps {
    isActive?: boolean;
}

export function EmbedButton(props: PropsWithChildren<IProps>) {
    const { isActive, children, buttonRef, ...otherProps } = props;
    const ownRef = useRef<HTMLButtonElement | null>(null);
    const [isFocusable, setIsFocusable] = useState(false);

    const checkFocusable = useCallback((activeElement: Element | null) => {
        if (
            ownRef.current === activeElement ||
            (isFirstNonDisabledSibling(ownRef.current) && !isSiblingFocused(ownRef.current, activeElement))
        ) {
            setIsFocusable(true);
        } else {
            setIsFocusable(false);
        }
    }, []);

    useLayoutEffect(() => {
        checkFocusable(document.activeElement);
    }, [checkFocusable]);

    return (
        <Button
            buttonRef={(button) => {
                if (typeof buttonRef === "function") {
                    buttonRef(button);
                } else if (buttonRef && typeof buttonRef === "object") {
                    (buttonRef as any).current = button;
                }
                ownRef.current = button;
                if (!button) {
                    return;
                }
            }}
            onFocus={(e) => {
                checkFocusable(e.target);
            }}
            onBlur={(e) => {
                if (isFocusable && !isSiblingFocused(ownRef.current, e.relatedTarget as Element)) {
                    // Leave ourself as focusable.
                    return;
                } else {
                    checkFocusable(e.relatedTarget as Element);
                }
            }}
            tabIndex={isFocusable ? 0 : -1}
            buttonType={ButtonTypes.ICON}
            onKeyDown={(e: React.KeyboardEvent<any>) => {
                props.onKeyDown?.(e);
                if (e.isPropagationStopped()) {
                    return;
                }
                const key = e.key;
                let target = e.currentTarget as HTMLElement & EventTarget;
                switch (key) {
                    case "ArrowRight":
                    case "ArrowDown": {
                        e.preventDefault();
                        const elementToFocus =
                            nextNonDisabledSibling(target) ?? firstNonDisabledChild(target.parentElement!);
                        if (elementToFocus instanceof HTMLElement) {
                            elementToFocus.focus();
                        }
                        break;
                    }
                    case "ArrowUp":
                    case "ArrowLeft": {
                        e.preventDefault();
                        const elementToFocus =
                            previousNonDisabledSibling(target) ?? lastNonDisabledChild(target.parentElement!);
                        if (elementToFocus instanceof HTMLElement) {
                            elementToFocus.focus();
                        }
                        break;
                    }
                    case "Home": {
                        e.preventDefault();
                        const elementToFocus = firstNonDisabledChild(target.parentElement!);
                        if (elementToFocus instanceof HTMLElement) {
                            elementToFocus.focus();
                        }
                        break;
                    }
                    case "End": {
                        e.preventDefault();
                        const elementToFocus = lastNonDisabledChild(target.parentElement!);
                        if (elementToFocus instanceof HTMLElement) {
                            elementToFocus.focus();
                        }
                        break;
                    }
                    default:
                        break;
                }
            }}
            {...otherProps}
        >
            {children}
        </Button>
    );
}

function isSiblingFocused(element: Element | null, activeElement: Element | null): boolean {
    if (!element?.parentElement) {
        return false;
    }

    if (activeElement !== element && element.parentElement.contains(activeElement)) {
        return true;
    }
    return false;
}

function nextNonDisabledSibling(element: Element): Element | null {
    let sibling = element.nextElementSibling;
    while (sibling !== null) {
        if (!isElementDisabled(sibling)) {
            return sibling;
        }
        sibling = sibling.nextElementSibling;
    }
    return null;
}

function previousNonDisabledSibling(element: Element): Element | null {
    let sibling = element.previousElementSibling;
    while (sibling !== null) {
        if (!isElementDisabled(sibling)) {
            return sibling;
        }
        sibling = sibling.previousElementSibling;
    }
    return null;
}

function firstNonDisabledChild(element: Element): Element | null {
    const child = element.firstElementChild;
    if (!child) {
        return null;
    }
    if (!isElementDisabled(child)) {
        return child;
    } else {
        return nextNonDisabledSibling(child);
    }
}

function lastNonDisabledChild(element: Element): Element | null {
    const child = element.lastElementChild;
    if (!child) {
        return null;
    }
    if (!isElementDisabled(child)) {
        return child;
    } else {
        return previousNonDisabledSibling(child);
    }
}

function isFirstNonDisabledSibling(element: Element | null): boolean {
    return !!element?.parentElement && element === firstNonDisabledChild(element.parentElement);
}
function isElementDisabled(element: Element) {
    return element.hasAttribute("disabled") && element.getAttribute("disabled") != "false";
}
