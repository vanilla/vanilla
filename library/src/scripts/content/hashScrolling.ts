/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { forceRenderStyles } from "typestyle";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { useEffect } from "react";
import { initAllUserContent } from "@library/content/index";

let previousHashOffset = 0;

/**
 * Hook for handling hash scrolling and user content rendering.
 *
 * @param content The content being displayed. Pass this to refresh the content when filled.
 * @param disabled Whether or not hash scrolling is disabled.
 */
export function useHashScrolling(content: string, disabled?: boolean) {
    const { temporarilyDisabledWatching, getCalcedHashOffset } = useScrollOffset();
    const calcedOffset = getCalcedHashOffset();
    previousHashOffset = calcedOffset;

    useEffect(() => {
        if (disabled) {
            return;
        }
        void initAllUserContent().then(() => {
            initHashScrolling(calcedOffset, () => temporarilyDisabledWatching(500));
        });
    }, [calcedOffset, temporarilyDisabledWatching, disabled, content]);
}

/**
 * Scroll to a particular HTML element.
 */
export function scrollToElement(element?: HTMLElement, offset?: number, beforeScrollHandler?: () => void) {
    const currentOffset = offset == null ? previousHashOffset : offset;
    if (element) {
        forceRenderStyles();
        beforeScrollHandler && beforeScrollHandler();
        setTimeout(() => {
            const top = window.pageYOffset + element.getBoundingClientRect().top - currentOffset;
            window.scrollTo({ top, behavior: "smooth" });
        }, 10);
    }
}

/**
 * Scroll to the window's current hash value.
 */
export function scrollToCurrentHash(offset?: number, beforeScrollHandler?: () => void) {
    const currentOffset = offset == null ? previousHashOffset : offset;
    const targetID = window.location.hash.replace("#", "");
    const element =
        (document.querySelector(`[data-id="${targetID}"]`) as HTMLElement) || document.getElementById(targetID);
    scrollToElement(element, currentOffset, beforeScrollHandler);
}

export function initHashScrolling(offset: number = 0, beforeScrollHandler?: () => void) {
    scrollToCurrentHash(offset, beforeScrollHandler);
    const hashChangeHandler = (e: HashChangeEvent) => {
        e.preventDefault();
        scrollToCurrentHash(offset, beforeScrollHandler);
    };

    window.addEventListener("hashchange", hashChangeHandler);
    return () => {
        window.removeEventListener("hashchange", hashChangeHandler);
    };
}
