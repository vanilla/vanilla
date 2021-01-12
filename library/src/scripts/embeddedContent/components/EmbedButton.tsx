/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button, { IButtonProps } from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import classNames from "classnames";
import React, { PropsWithChildren, KeyboardEvent, useLayoutEffect, useState, useRef } from "react";
import { embedButtonClasses } from "./embedButtonStyles";

interface IProps extends IButtonProps {
    isActive?: boolean;
}

export function EmbedButton(props: PropsWithChildren<IProps>) {
    const { isActive, children, buttonRef, onKeyDown, ...otherProps } = props;
    const classes = embedButtonClasses();
    const [isFirst, setIsFirst] = useState(false);

    return (
        <Button
            buttonRef={(button) => {
                if (typeof buttonRef === "function") buttonRef(button);
                else if (buttonRef && typeof buttonRef === "object") (buttonRef as any).current = button;
                if (!button) return;
                setIsFirst(button.previousSibling === null);
            }}
            tabIndex={isFirst ? 0 : -1}
            onKeyDown={(e: React.KeyboardEvent<HTMLElement>) => {
                const key = e.key;
                let target = e.target as HTMLElement & EventTarget;
                const firstSibling = () => target.parentElement?.firstChild as HTMLElement | undefined;
                const lastSibling = () => target.parentElement?.lastChild as HTMLElement | undefined;
                switch (key) {
                    case "ArrowRight": {
                        e.preventDefault();
                        let nextSibling = target.nextSibling as HTMLElement | undefined;
                        if (!nextSibling && target.parentElement) {
                            nextSibling = firstSibling();
                        }
                        (nextSibling as HTMLElement).focus();
                        break;
                    }
                    case "ArrowLeft": {
                        e.preventDefault();
                        let previousSibling = target.previousSibling as HTMLElement | undefined;
                        if (!previousSibling && target.parentElement) {
                            previousSibling = lastSibling();
                        }
                        (previousSibling as HTMLElement).focus();
                        break;
                    }
                    case "Home": {
                        e.preventDefault();
                        (firstSibling() as HTMLElement)?.focus();
                        break;
                    }
                    case "End": {
                        e.preventDefault();
                        (lastSibling() as HTMLElement)?.focus();
                        break;
                    }
                    default:
                        break;
                }
                if (onKeyDown) onKeyDown(e);
            }}
            baseClass={ButtonTypes.CUSTOM}
            className={classNames({ [classes.button]: true, isActive })}
            {...otherProps}
        >
            {children}
        </Button>
    );
}
