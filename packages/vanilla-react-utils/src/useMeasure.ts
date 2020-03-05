/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { RefObject, useState, useLayoutEffect } from "react";
import ResizeObserver from "resize-observer-polyfill";
import debounce from "lodash/debounce";

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
 */
export function useMeasure(ref: RefObject<HTMLElement | null>, adjustForScrollOffset: boolean = false) {
    const [bounds, setContentRect] = useState<DOMRect>(EMPTY_RECT);

    useLayoutEffect(() => {
        let animationFrameId: number | null = null;

        const measure = () => {
            animationFrameId = window.requestAnimationFrame(() => {
                if (!ref.current) {
                    return;
                }
                let rect = ref.current.getBoundingClientRect();

                if (adjustForScrollOffset) {
                    rect = {
                        ...rect,
                        y: rect.y + window.scrollY,
                        top: rect.top + window.scrollY,
                        bottom: rect.bottom + window.scrollY,
                        width: rect.width,
                        height: rect.height,
                        right: rect.right,
                        left: rect.left,
                    };
                }

                setContentRect(rect);
            });
        };

        const resizeListener = debounce(() => {
            measure();
        }, 100);
        window.addEventListener("resize", resizeListener);

        const ro = new ResizeObserver(measure);
        if (ref.current) {
            ro.observe(ref.current);
        }

        return () => {
            window.cancelAnimationFrame(animationFrameId!);
            ro.disconnect();
            resizeListener.cancel();
            window.removeEventListener("resize", resizeListener);
        };
    }, [adjustForScrollOffset, ref]);

    return bounds;
}
