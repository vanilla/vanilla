/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { forceRenderStyles } from "typestyle";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { useCallback, useEffect } from "react";

export function useHashScrolling() {
    const offset = useScrollOffset();
    const calcedOffset = offset.getCalcedHashOffset();
    const callback = useCallback(() => {
        return initHashScrolling(calcedOffset, () => offset.temporarilyDisabledWatching(500));
    }, [offset, calcedOffset]);

    useEffect(() => {
        callback();
    }, [callback]);
}

export function initHashScrolling(offset: number = 0, beforeScrollHandler?: () => void) {
    /**
     * Scroll to the window's current hash value.
     */
    const scrollToHash = (event?: HashChangeEvent) => {
        event && event.preventDefault();

        const targetID = window.location.hash.replace("#", "");
        const element =
            (document.querySelector(`[data-id="${targetID}"]`) as HTMLElement) || document.getElementById(targetID);
        if (element) {
            forceRenderStyles();
            beforeScrollHandler && beforeScrollHandler();
            // setTimeout(() => {
            const top = window.pageYOffset + element.getBoundingClientRect().top - offset;
            window.scrollTo({ top, behavior: "smooth" });
            // },)
        }
    };

    scrollToHash();

    window.addEventListener("hashchange", scrollToHash);
    return () => {
        window.removeEventListener("hashchange", scrollToHash);
    };
}
