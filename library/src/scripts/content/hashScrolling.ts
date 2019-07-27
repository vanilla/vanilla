/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { forceRenderStyles } from "typestyle";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { useCallback, useEffect } from "react";
import { initAllUserContent } from "@library/content/index";

export function useHashScrolling() {
    const { temporarilyDisabledWatching, getCalcedHashOffset } = useScrollOffset();
    const calcedOffset = getCalcedHashOffset();

    useEffect(() => {
        void initAllUserContent().then(() => {
            initHashScrolling(calcedOffset, () => temporarilyDisabledWatching(500));
        });
    }, [calcedOffset, temporarilyDisabledWatching]);
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
            setTimeout(() => {
                const top = window.pageYOffset + element.getBoundingClientRect().top - offset;
                window.scrollTo({ top, behavior: "smooth" });
            }, 10);
        }
    };

    scrollToHash();

    window.addEventListener("hashchange", scrollToHash);
    return () => {
        window.removeEventListener("hashchange", scrollToHash);
    };
}
