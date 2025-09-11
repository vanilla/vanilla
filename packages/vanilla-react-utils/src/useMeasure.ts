/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    RefObject,
    useState,
    useLayoutEffect,
    MutableRefObject,
    useMemo,
    useRef,
    useEffect,
    useDebugValue,
} from "react";
import debounce from "lodash-es/debounce";
import { useIsMounted } from "./useIsMounted";

export type MeasuredRect = {
    x: number;
    y: number;
    width: number;
    // Adjusted for scale
    clientWidth: number;
    scrollWidth: number;
    height: number;
    // Adjusted for scale
    clientHeight: number;
    scrollHeight: number;
    top: number;
    right: number;
    bottom: number;
    left: number;
};

export const EMPTY_RECT: MeasuredRect = {
    x: 0,
    y: 0,
    width: 0,
    clientWidth: 0,
    scrollWidth: 0,
    height: 0,
    clientHeight: 0,
    scrollHeight: 0,
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
};

/**
 * Utility hook for measuring a dom element.
 * Will return back measurements as a bounding rectangle for the element contained in a ref.
 *
 * @param ref The ref to measure.
 * @param adjustForScrollOffset If set, y values will be adjusted for the current scroll offset.
 * @param watchRef Used to trigger a remeasure if the ref changes.
 *
 * @public
 * @package @vanilla/injectables/Utils
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
    const [rect, setRect] = useState<MeasuredRect>(ref.current ? getRect(ref.current, offsetScroll) : EMPTY_RECT);
    const rectRef = useRef(rect);
    useEffect(() => {
        rectRef.current = rect;
    });
    const isMounted = useIsMounted();
    // memoize handlers, so event-listeners know when they should update
    const [forceRefresh, scrollChange, resizeChange] = useMemo(() => {
        const callback = () => {
            if (!ref.current) {
                return;
            }
            let rect = getRect(ref.current, offsetScroll);

            if (isMounted() && !areBoundsEqual(rectRef.current, rect)) {
                setRect(rect);
            }
        };
        return [
            callback,
            scrollDebounce ? debounce(callback, scrollDebounce) : callback,
            resizeDebounce ? debounce(callback, resizeDebounce) : callback,
        ];
    }, [watchRef ? ref.current : undefined, setRect, offsetScroll, scrollDebounce, resizeDebounce]);

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
            setRect(EMPTY_RECT);
        };
    }, [watchScroll, forceRefresh, resizeChange, scrollChange, watchRef ? ref.current : undefined, ref]);

    useDebugValue(rect);

    return rect;
}

function getRect(element: HTMLElement, offsetScroll: boolean): MeasuredRect {
    let domRect = element.getBoundingClientRect();

    // Make sure things respect CSS scaling
    let measuredRect: MeasuredRect = {
        top: domRect.top,
        left: domRect.left,
        right: domRect.right,
        bottom: domRect.bottom,
        x: domRect.x,
        y: domRect.y,
        width: domRect.width,
        clientWidth: element.clientWidth,
        scrollWidth: element.scrollWidth,
        height: domRect.height,
        clientHeight: element.clientHeight,
        scrollHeight: element.scrollHeight,
    };

    if (offsetScroll) {
        measuredRect = {
            ...measuredRect,
            y: measuredRect.y + window.scrollY,
            top: measuredRect.top + window.scrollY,
            bottom: measuredRect.bottom + window.scrollY,
        };
    }
    return measuredRect;
}

// Returns a list of scroll offsets
function findScrollContainers(element: HTMLElement | null): HTMLElement[] {
    const result: HTMLElement[] = [];
    if (!element || element === document.body) return result;
    const { overflow, overflowX, overflowY } = window.getComputedStyle(element);
    if ([overflow, overflowX, overflowY].some((prop) => prop === "auto" || prop === "scroll")) result.push(element);
    return [...result, ...findScrollContainers(element.parentElement)];
}

const keys = Object.keys(EMPTY_RECT);
const areBoundsEqual = (a: MeasuredRect, b: MeasuredRect): boolean => keys.every((key) => a[key] === b[key]);
