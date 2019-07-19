/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { RefObject, useState, useLayoutEffect } from "react";
import ResizeObserver from "resize-observer-polyfill";

/**
 * Utility hook for measuring a dom element.
 * Will return back measurements as a bounding rectangle for the element contained in a ref.
 */
export function useMeasure(ref: RefObject<HTMLElement | null>) {
    const [bounds, setContentRect] = useState<DOMRectReadOnly>(
        // DOMRectReadOnly.fromRect()
        { x: 0, y: 0, width: 0, height: 0, top: 0, right: 0, bottom: 0, left: 0, toJSON: () => "" },
    );

    useLayoutEffect(() => {
        let animationFrameId: number | null = null;
        const measure: ResizeObserverCallback = ([entry]) => {
            animationFrameId = window.requestAnimationFrame(() => {
                setContentRect(ref.current!.getBoundingClientRect() as any);
            });
        };

        const ro = new ResizeObserver(measure);
        if (ref.current) {
            ro.observe(ref.current);
        }

        return () => {
            window.cancelAnimationFrame(animationFrameId!);
            ro.disconnect();
        };
    }, [ref]);

    return bounds;
}
