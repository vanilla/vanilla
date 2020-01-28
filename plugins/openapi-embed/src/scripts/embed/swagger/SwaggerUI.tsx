/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SwaggerUI from "swagger-ui-react/swagger-ui";
import { scrollToElement } from "@vanilla/library/src/scripts/content/hashScrolling";
import "@openapi-embed/embed/swagger/swaggerStyles.scss";

replaceDeepLinkScrolling(SwaggerUI);

/**
 * Stub out the scroll to method.
 *
 * Swagger currently uses a really weird way to scroll (scrolling done in JS).
 * Stub it out and use our own scrolling method that accounts for header offset.
 */
function replaceDeepLinkScrolling(swagger: any) {
    swagger.plugins.DeepLinkingLayout.statePlugins.layout.actions.scrollToElement = (elementToScroll, container) => {
        scrollToElement(elementToScroll);
        return () => {};
    };
}

export { SwaggerUI };
