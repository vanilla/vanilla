/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React, { useEffect, useState } from "react";
import { toastClasses } from "@library/features/toaster/Toast.styles";
import { cx } from "@library/styles/styleShim";
import { EntranceAnimation, FromDirection } from "@library/animation/EntranceAnimation";
import CloseButton from "@library/navigation/CloseButton";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    /** Duration in seconds that the toast should self dismiss */
    autoCloseDuration?: number;
    /** The current toast visibility */
    visibility?: boolean;
    /** Function to set the toasts visibility */
    onVisibilityChange?(visibility: boolean): void;
    /** The role of the toast */
    role?: "alert" | "progressbar" | "status";
    /** ClassName added to the toast element */
    className?: string;
    /** If a close button be rendered */
    dismissible?: boolean;

    wide?: boolean;

    portal?: boolean;
}

/**
 * Render a toast component
 */
export function Toast(props: IProps) {
    const { children, portal, role, className, visibility, autoCloseDuration, onVisibilityChange, wide, dismissible } =
        props;

    const classes = toastClasses();

    /** Internal visibility state */
    const [display, setDisplay] = useState(visibility ?? true);

    useEffect(() => {
        if (autoCloseDuration && display) {
            setTimeout(() => setDisplay(false), autoCloseDuration);
        }
    }, [display, autoCloseDuration]);

    useEffect(() => {
        onVisibilityChange && onVisibilityChange(display);
    }, [display, onVisibilityChange]);

    useEffect(() => {
        setDisplay(!!visibility);
    }, [visibility]);

    const result = (
        <EntranceAnimation isEntered={display} fromDirection={FromDirection.LEFT}>
            <div
                className={cx(classes.root, className, wide && classes.wide)}
                role={role ?? "status"}
                aria-live={"assertive"}
                aria-atomic={true}
            >
                {dismissible && (
                    <CloseButton className={classes.closeButton} onClick={() => setDisplay(false)} compact />
                )}

                {children}
            </div>
        </EntranceAnimation>
    );

    if (props.portal) {
        const target = document.getElementById("portaled-toasts");
        if (!target) {
            return <></>;
        }
        return ReactDOM.createPortal(result, target as HTMLElement);
    } else {
        return result;
    }
}
