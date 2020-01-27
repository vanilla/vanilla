/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { History } from "history";
import { useEffect } from "react";

export type PageViewHandler = (params: { history: History }) => void | Promise<void>;

const EVENT_NAME = "pageView";

/**
 * Register a callback for a when a pageview is triggered.
 *
 * @param handler The handler for the page view.
 */
export function onPageView(handler: PageViewHandler) {
    const eventListener = (e: CustomEvent) => {
        const history = e.detail;
        handler(history);
    };
    document.addEventListener(EVENT_NAME, eventListener);
    return () => {
        document.removeEventListener(EVENT_NAME, eventListener);
    };
}

export function usePageChangeListener(handler: PageViewHandler) {
    useEffect(() => {
        return onPageView(handler);
    }, [handler]);
}

window.onPageView = onPageView;

let previousPath: string | null = null;

function getPreviousPath() {
    return previousPath;
}

function setPreviousPath(path: string) {
    previousPath = path;
}

/**
 * Initialize tracking of the page history and fire the handlers for the current page.
 */
export function initPageViewTracking(history: History) {
    document.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: history }));
    setPreviousPath(history.location.pathname);

    history.listen(() => {
        if (getPreviousPath() !== history.location.pathname) {
            document.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: history }));
        }

        setPreviousPath(history.location.pathname);
    });
}
