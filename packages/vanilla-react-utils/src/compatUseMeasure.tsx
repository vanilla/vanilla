/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { RefObject, useState, useLayoutEffect, MutableRefObject, useMemo, useRef, useEffect } from "react";
import debounce from "lodash-es/debounce";
import { useIsMounted } from "./useIsMounted";

// DOMRectReadOnly.fromRect()
const EMPTY_RECT: DOMRect = {
    x: 0,
    y: 0,
    width: 0,
    height: 0,
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    toJSON: () => "",
};

/**
 * Utility hook for measuring a dom element.
 * Will return back measurements as a bounding rectangle for the element contained in a ref.
 *
 * @param ref The ref to measure.
 * @param adjustForScrollOffset If set, y values will be adjusted for the current scroll offset.
 * @param watchRef Used to trigger a remeasure if the ref changes.
 *
 * @deprecated
 * Use `useMeasure` from `@vanilla/react-utils` instead.
 */
export function useMeasure(
    ref: RefObject<HTMLElement | null> | MutableRefObject<HTMLElement | null | undefined>,
    options?: {
        watchRef?: boolean;
        watchScroll?: boolean;
        offsetScroll?: boolean;
        scrollDebounce?: number;
        resizeDebounce?: number;
    },
) {
    const {
        watchRef = false,
        offsetScroll = false,
        watchScroll = false,
        scrollDebounce = 0,
        resizeDebounce = 100,
    } = options ?? {};
    const [bounds, setContentRect] = useState<DOMRect>(EMPTY_RECT);
    const boundsRef = useRef(bounds);
    useEffect(() => {
        boundsRef.current = bounds;
    });
    const isMounted = useIsMounted();
    // memoize handlers, so event-listeners know when they should update
    const [forceRefresh, scrollChange, resizeChange] = useMemo(() => {
        const callback = () => {
            if (!ref.current) {
                return;
            }
            let rect = ref.current.getBoundingClientRect();

            if (offsetScroll) {
                rect = {
                    ...rect,
                    y: rect.y + window.scrollY,
                    top: rect.top + window.scrollY,
                    bottom: rect.bottom + window.scrollY,
                };
            }
            rect.toJSON = () => {
                return JSON.stringify({
                    y: rect.y,
                    top: rect.top,
                    bottom: rect.bottom,
                    width: rect.width,
                    height: rect.height,
                    right: rect.right,
                    left: rect.left,
                });
            };

            if (isMounted() && !areBoundsEqual(boundsRef.current, rect)) {
                setContentRect(rect);
            }
        };
        return [
            callback,
            scrollDebounce ? debounce(callback, scrollDebounce) : callback,
            resizeDebounce ? debounce(callback, resizeDebounce) : callback,
        ];
    }, [watchRef ? ref.current : undefined, setContentRect, offsetScroll, scrollDebounce, resizeDebounce]);

    useLayoutEffect(() => {
        let scrollContainers = ref.current ? findScrollContainers(ref.current) : [];
        const ro = new ResizeObserver(resizeChange);
        function addListeners() {
            if (!ref.current) {
                return;
            }
            if (watchScroll) {
                window.addEventListener("scroll", scrollChange, { capture: true, passive: true });
                scrollContainers.forEach((container) => {
                    container.addEventListener("scroll", scrollChange, { capture: true, passive: true });
                });
            }

            if ("orientation" in screen) {
                screen.orientation.addEventListener("change", scrollChange);
            }

            window.addEventListener("resize", resizeChange);

            ro.observe(ref.current);
        }

        function removeListeners() {
            if (watchScroll) {
                scrollContainers.forEach((container) => {
                    container.removeEventListener("scroll", scrollChange, { capture: true });
                });

                window.removeEventListener("scroll", scrollChange, { capture: true });
            }

            if ("orientation" in screen) {
                screen.orientation.removeEventListener("change", scrollChange);
            }

            window.removeEventListener("resize", resizeChange);
            ro.disconnect();
        }

        forceRefresh();
        addListeners();

        return () => {
            removeListeners();
            setContentRect(EMPTY_RECT);
        };
    }, [watchScroll, forceRefresh, resizeChange, scrollChange, watchRef ? ref.current : undefined, ref]);

    return bounds;
}

// Returns a list of scroll offsets
function findScrollContainers(element: HTMLElement | null): HTMLElement[] {
    const result: HTMLElement[] = [];
    if (!element || element === document.body) return result;
    const { overflow, overflowX, overflowY } = window.getComputedStyle(element);
    if ([overflow, overflowX, overflowY].some((prop) => prop === "auto" || prop === "scroll")) result.push(element);
    return [...result, ...findScrollContainers(element.parentElement)];
}

const keys: (keyof DOMRect)[] = ["x", "y", "top", "bottom", "left", "right", "width", "height"];
const areBoundsEqual = (a: DOMRect, b: DOMRect): boolean => keys.every((key) => a[key] === b[key]);
