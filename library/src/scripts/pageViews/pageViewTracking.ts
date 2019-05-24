/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { History } from "history";

export type PageViewHandler = (params: { history: History }) => void | Promise<void>;
const handlers: PageViewHandler[] = [];

/**
 * Register a callback for a when a pageview is triggered.
 *
 * @param handler The handler for the page view.
 */
export function onPageView(handler: PageViewHandler) {
    handlers.push(handler);
}

/**
 * Initialize tracking of the page history and fire the handlers for the current page.
 */
export function initPageViewTracking(history: History) {
    const callHandlers = () => {
        handlers.forEach(handler => handler({ history }));
    };

    callHandlers();
    history.listen(callHandlers);
}
