/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useDebugValue, useEffect, useState } from "react";
import { useMeasure, type MeasuredRect } from "./useMeasure";
import { useStatefulRef } from "./useStatefulRef";

/**
 * Return a ref and a boolean indicating if the element is overflowing.
 *
 * If the item previously overflowed, it will continue to return true
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function useIsOverflowing(): {
    measure: MeasuredRect;
    ref: React.RefObject<any>;
    isOverflowing: boolean;
    scrollX: number;
    scrollY: number;
} {
    const ref = useStatefulRef<HTMLElement | null>(null);
    const [didOverflow, setDidOverflow] = useState(false);
    const measure = useMeasure(ref, { watchRef: true });

    const [scroll, setScroll] = useState({ scrollX: 0, scrollY: 0 });
    useEffect(() => {
        if (ref.current) {
            const listener = (e: Event) => {
                const target = e.target as HTMLElement;
                setScroll({ scrollX: target.scrollLeft, scrollY: target.scrollTop });
            };
            ref.current.addEventListener("scroll", listener, { passive: true });

            return () => {
                ref.current?.removeEventListener("scroll", listener);
            };
        }
    }, [ref.current]);

    const buffer = 4; // 2px for border
    const isWidthOverflowing = measure.clientWidth + buffer < measure.scrollWidth;
    const isHeightOverflowing = measure.clientHeight + buffer < measure.scrollHeight;
    let isOverflowing = isWidthOverflowing || isHeightOverflowing;
    isOverflowing = ref.current != null && isOverflowing && (measure.width > 0 || measure.height > 0);

    // Overflow is a one way street.
    // Typically if we are overflowing we will hide an element.
    // If we allowed flipping back and forth this could cause the element to overflow,
    // then hide itself (and not overflow), then back and forth flickering.
    isOverflowing = isOverflowing || didOverflow;

    useEffect(() => {
        if (isOverflowing && !didOverflow) {
            setDidOverflow(true);
        }
    }, [didOverflow, isOverflowing]);

    useDebugValue({ didOverflow, isOverflowing, isWidthOverflowing, isHeightOverflowing });

    return { ref, measure, isOverflowing, ...scroll };
}
