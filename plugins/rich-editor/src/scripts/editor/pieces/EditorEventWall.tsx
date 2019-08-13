/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useEffect } from "react";

interface IProps extends React.PropsWithoutRef<JSX.IntrinsicElements["div"]> {}

const EDITOR_WALLED_EVENT = "isEditorWalledEvent";
const EDITOR_EVENT_WALL_CLASS = "editorEventWall";

/**
 * A react component that prevents events from propagating up to quill.
 *
 * This marks events so they can be ignored by our own event handlers in quill.
 * Currently this is only implemented for the FocusModule. See usages of isEditorWalledEvent
 */
export function EditorEventWall(props: IProps) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const { current } = ref;
        if (!current) {
            return;
        }

        current.addEventListener("click", eventFlagger);
        current.addEventListener("mouseup", eventFlagger);
        current.addEventListener("mousedown", eventFlagger);
        current.addEventListener("keydown", eventFlagger);
        return () => {
            current.removeEventListener("click", eventFlagger);
            current.removeEventListener("mouseup", eventFlagger);
            current.removeEventListener("mousedown", eventFlagger);
            current.removeEventListener("keydown", eventFlagger);
        };
    }, [ref]);

    return (
        <div
            ref={ref}
            {...props}
            onClick={e => {
                e.preventDefault();
            }}
            className={EDITOR_EVENT_WALL_CLASS}
        />
    );
}

/**
 * Event listener that flags items originating from inside of HTMLElement inside an event wall.
 * @param event
 */
const eventFlagger = (event: Event) => {
    event[EDITOR_WALLED_EVENT] = true;
};

/**
 * Determine if an event has been marked to be walled.
 */
export function isEditorWalledEvent(event: Event): boolean {
    return !!(event as any)[EDITOR_WALLED_EVENT];
}
