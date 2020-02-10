/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { scrollToElement } from "@library/content/hashScrolling";

/**
 * Stub out the scroll to method.
 *
 * Swagger currently uses a really weird way to scroll (scrolling done in JS).
 * Stub it out and use our own scrolling method that accounts for header offset.
 */
export function replaceDeepLinkScrolling(swagger: any, offset?: number) {
    swagger.plugins.DeepLinkingLayout.statePlugins.layout.actions.scrollToElement = (elementToScroll, container) => {
        scrollToElement(elementToScroll, offset);
        return () => {};
    };
}
