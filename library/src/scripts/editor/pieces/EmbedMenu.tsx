/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { embedMenuClasses } from "@library/editor/pieces/embedMenuStyles";
import { TabHandler } from "@vanilla/dom-utils";
import { mergeRefs } from "@vanilla/react-utils";
import React, { forwardRef, PropsWithChildren, useRef } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

export const EmbedMenu = forwardRef(function EmbedMenu(
    props: PropsWithChildren<IProps> & { isOpened?: boolean },
    ref: React.Ref<HTMLDivElement>,
) {
    const { children, isOpened, ...restProps } = props;
    const classes = embedMenuClasses.useAsHook();
    const ownRef = useRef<HTMLDivElement>(null);
    const toolbarProps = useToolbar(ownRef);
    return (
        <div
            {...restProps}
            {...toolbarProps}
            ref={mergeRefs(ownRef, ref)}
            className={cx(classes.root, { isOpened }, props.className)}
        >
            {children}
        </div>
    );
});

function useToolbar(ref: React.RefObject<HTMLDivElement | null>) {
    // Record the last focused child when focus moves out of the toolbar.
    const lastFocused = useRef<HTMLElement | null>(null);
    const onBlur = (e: React.FocusEvent<HTMLElement>) => {
        if (!e.currentTarget.contains(e.relatedTarget) && !lastFocused.current) {
            lastFocused.current = e.target;
        }
    };

    // Restore focus to the last focused child when focus returns into the toolbar.
    // If the element was removed, do nothing, either the first item in the first group,
    // or the last item in the last group will be focused, depending on direction.
    const onFocus = (e: React.FocusEvent<HTMLElement>) => {
        if (lastFocused.current && !e.currentTarget.contains(e.relatedTarget) && ref.current?.contains(e.target)) {
            lastFocused.current?.focus();
            lastFocused.current = null;
        }
    };

    const onKeyDown = (e: React.KeyboardEvent<HTMLElement>) => {
        if (!ref.current) {
            return;
        }

        // don't handle portalled events
        if (!e.currentTarget.contains(e.target as HTMLElement)) {
            return;
        }

        const tabHandler = new TabHandler(ref.current);
        if (e.key === "ArrowRight" || e.key === "ArrowDown") {
            tabHandler.getNext(lastFocused.current ?? document.activeElement, false, true)?.focus();
            e.stopPropagation();
            e.preventDefault();
        } else if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
            tabHandler.getNext(lastFocused.current ?? document.activeElement, true, true)?.focus();
            e.stopPropagation();
            e.preventDefault();
        } else if (e.key === "Tab") {
            // When the tab key is pressed, we want to move focus
            // out of the entire toolbar. To do this, move focus
            // to the first or last focusable child, and let the
            // browser handle the Tab key as usual from there.
            e.stopPropagation();

            lastFocused.current = document.activeElement as HTMLElement;
            if (e.shiftKey) {
                // Shift + Tab
                tabHandler.getInitial()?.focus();
            } else {
                tabHandler.getLast()?.focus();
            }
        }
    };

    return {
        role: "toolbar",
        onBlurCapture: onBlur,
        onFocusCapture: onFocus,
        onKeyDownCapture: onKeyDown,
    };
}
