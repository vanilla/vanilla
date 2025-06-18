/**
 * @copyright 2025 Adam (charrondev) Charron
 * @license MIT
 */

import { useState, useRef, useCallback, useLayoutEffect } from "react";
import { useStatefulRef } from "./useStatefulRef";

/**
 * Hook that can animate a size transition of an element.
 *
 * @example
 *
 * ```tsx
 * const { runWithTransition, measureRef } = useSizeAnimator();
 * const [isOpen, setIsOpen] = useState(false);
 * return (
 *     <div ref={measureRef} style={{}}>
 *         <button
 *              onClick={() => {
 *                  runWithTransition(() => {
 *                       // Do your state change here
 *                       setIsOpen((prev) => !prev);
 *                  });
 *              }}>
 *              Hello button
 *          </button>
 *     </div>
 * )
 * ```
 *
 * @returns {{
 *      runWithTransition: (callback: () => void) => void;
 *      measureRef: React.RefObject<HTMLElement>;
 *  }}
 */
export function useSizeAnimator() {
    const measureRef = useStatefulRef<HTMLElement | null>(null);
    const [counter, setCounter] = useState(0);
    const [isTransitioning, setIsTransitioning] = useState(false);

    const animationRef = useRef<{
        width: number;
        height: number;
        isAnimating: boolean;
    }>({ width: 0, height: 0, isAnimating: false });

    const runWithTransition = useCallback((callback: () => void) => {
        if (measureRef.current) {
            // Get current dimensions before changing content
            const { offsetWidth, offsetHeight } = measureRef.current;
            animationRef.current = {
                width: offsetWidth,
                height: offsetHeight,
                isAnimating: true,
            };
            setIsTransitioning(true);

            // We're keeping a counter to ensure our effect still retriggers if we start a new transition while an existing one is running.
            setCounter((prev) => prev + 1);
        }
        callback();
    }, []);

    // Measure and apply new dimensions after content change
    useLayoutEffect(() => {
        if (measureRef.current && isTransitioning) {
            const element = measureRef.current;

            // Force the element to the previous dimensions
            const prevDimensions = animationRef.current;
            if (prevDimensions.isAnimating) {
                const targetCssProperties = {
                    width: getComputedStyle(element).width,
                    height: getComputedStyle(element).height,
                    maxWidth: getComputedStyle(element).maxWidth,
                    minWidth: getComputedStyle(element).minWidth,
                };

                element.style.width = `${prevDimensions.width}px`;
                element.style.height = `${prevDimensions.height}px`;
                element.style.overflow = "hidden";

                // Get new natural dimensions
                const clone = element.cloneNode(true) as HTMLElement;
                clone.style.position = "absolute";
                clone.style.visibility = "hidden";
                clone.style.pointerEvents = "none";

                // Maintain the same classes and parent context for proper measurement
                for (const [key, value] of Object.entries(targetCssProperties)) {
                    clone.style[key as any] = value;
                }
                // Add to the same parent to inherit CSS context
                element.parentElement?.appendChild(clone);
                const newWidth = clone.offsetWidth;
                const newHeight = clone.offsetHeight;
                element.parentElement?.removeChild(clone);

                // Trigger animation to new dimensions
                const animationFrame = requestAnimationFrame(() => {
                    element.style.transition = "width 300ms ease-in-out, height 300ms ease-in-out";
                    // Override our max width
                    element.style.maxWidth = "none";
                    element.style.width = `${newWidth}px`;
                    element.style.height = `${newHeight}px`;
                });
                // Clean up after transition
                const onTransitionEnd = () => {
                    cancelAnimationFrame(animationFrame);
                    element.style.width = "";
                    element.style.maxWidth = "";
                    element.style.height = "";
                    element.style.minWidth = "";
                    element.style.overflow = "";
                    element.style.transition = "";
                    element.removeEventListener("transitionend", onTransitionEnd);
                    animationRef.current.isAnimating = false;
                    setIsTransitioning(false);
                };
                element.addEventListener("transitionend", onTransitionEnd);
                return () => {
                    // Cleanup in case we unmount before the transition ends
                    onTransitionEnd();
                };
            }
        }
    }, [counter, isTransitioning]);

    return {
        runWithTransition,
        // Casting to ensure it's not a "mutable" ref which some things aren't typed to take.
        measureRef: measureRef as React.RefObject<any>,
    };
}
