/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useRef, useEffect, useState, useDebugValue } from "react";
import { useMeasure } from "./useMeasure";

/**
 * Detect if the edges of 2 measures are overlapping.
 *
 * If the edges are touching, but not overlapping, that doesn't count.
 */
function doRectsOverlap(rect1: DOMRectReadOnly, rect2: DOMRectReadOnly) {
    // For all docblocks here, [] represents rect1 and {} represents rect 2.
    if (isEmptyRect(rect1) || isEmptyRect(rect2)) {
        return false;
    }

    let hasHorizontalOverlap = false;

    //  [      { ]       }
    if (rect1.left < rect2.left && rect1.right > rect2.left) {
        hasHorizontalOverlap = true;
    }

    //  {    [  }   ]
    if (rect2.left < rect1.left && rect2.right > rect1.left) {
        hasHorizontalOverlap = true;
    }

    //  [   {} ]
    if (rect1.left < rect2.left && rect1.right > rect2.right) {
        hasHorizontalOverlap = true;
    }

    //  {  []   }
    if (rect2.left < rect1.left && rect2.right > rect1.right) {
        hasHorizontalOverlap = true;
    }

    let hasVerticalOverlap = false;

    // Verical version of  [      { ]       }
    if (rect1.top < rect2.top && rect1.bottom > rect2.top) {
        hasVerticalOverlap = true;
    }

    // Verical version of  {    [  }   ]
    if (rect2.top < rect1.top && rect2.bottom > rect1.top) {
        hasVerticalOverlap = true;
    }

    // Verical version of  [   {} ]
    if (rect1.top < rect2.top && rect1.bottom > rect2.bottom) {
        hasVerticalOverlap = true;
    }

    // Verical version of  {  []   }
    if (rect2.top < rect1.top && rect2.bottom > rect1.bottom) {
        hasVerticalOverlap = true;
    }

    // Both overlaps are required to make a collision.
    return hasVerticalOverlap && hasHorizontalOverlap;
}

function isEmptyRect(rect: DOMRectReadOnly): boolean {
    return rect.height === 0 || rect.width === 0;
}

export function useCollisionDetector() {
    const collisionSourceRef = useRef<HTMLDivElement | null>(null);
    let sourceMeasure = useMeasure(collisionSourceRef);

    const vBoundary1Ref = useRef<HTMLDivElement | null>(null);
    const vBoundary1Measure = useMeasure(vBoundary1Ref);

    const vBoundary2Ref = useRef<HTMLDivElement | null>(null);
    const vBoundary2Measure = useMeasure(vBoundary2Ref);

    const hBoundary1Ref = useRef<HTMLDivElement | null>(null);
    const hBoundary1Measure = useMeasure(hBoundary1Ref);

    const hBoundary2Ref = useRef<HTMLDivElement | null>(null);
    const hBoundary2Measure = useMeasure(hBoundary2Ref);

    const hasCollision =
        doRectsOverlap(sourceMeasure, vBoundary1Measure) ||
        doRectsOverlap(sourceMeasure, vBoundary2Measure) ||
        doRectsOverlap(sourceMeasure, hBoundary1Measure) ||
        doRectsOverlap(sourceMeasure, hBoundary2Measure);

    useDebugValue({ hasCollision });

    return { collisionSourceRef, hasCollision, vBoundary1Ref, vBoundary2Ref, hBoundary1Ref, hBoundary2Ref };
}
