import { scrollToElement } from "@vanilla/library/src/scripts/content/hashScrolling";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Stub out the scroll to method.
 */
export function patchSwaggerDeepLinks(swagger: any) {
    swagger.plugins.DeepLinkingLayout.statePlugins.layout.actions.scrollToElement = (elementToScroll, container) => {
        scrollToElement(elementToScroll);
        console.log("Scroll to element", elementToScroll, container);
        return () => {};
    };
}
