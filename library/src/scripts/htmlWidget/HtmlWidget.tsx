/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useUniqueID } from "@library/utility/idUtils";
import { prepareShadowRoot } from "@vanilla/dom-utils";
import React, { useLayoutEffect, useRef } from "react";

interface IProps {
    name?: string;
    // Unsafe HTML. Never allow untrusted user input in here.
    html: string;
    css?: string;
    javascript?: string;
    javascriptNonce?: string;
}

/**
 * Configurable widget for rendering arbitrary HTML.
 */
export const HtmlWidget = React.memo(
    function HtmlWidget(props: IProps) {
        const ref = useRef<HTMLElement>(null);
        const widgetID = useUniqueID("htmlWidget");

        useLayoutEffect(() => {
            if (!ref.current) {
                return;
            }
            const element = prepareShadowRoot(ref.current, true);
            if (props.javascript && props.javascriptNonce) {
                if (!window.__VANILLA_HTML_WIDGET_NODES__) {
                    window.__VANILLA_HTML_WIDGET_NODES__ = {};
                }
                window.__VANILLA_HTML_WIDGET_NODES__[widgetID] = element;
                const scriptTag = document.createElement("script");
                scriptTag.nonce = lookupNonce(props.javascriptNonce);
                const scriptContents = `
(function () {
try {
    var customHtmlRoot = window.__VANILLA_HTML_WIDGET_NODES__['${widgetID}'];
    var vanilla = window.__VANILLA_GLOBALS_DO_NOT_USE_DIRECTLY__;
    ${props.javascript}
} catch (err) {
    console.error("Error occured in custom HTML widget: '${props.name}'.", err);
}})();
`;
                scriptTag.innerHTML = scriptContents;
                element.insertAdjacentElement("afterend", scriptTag);
            }
        }, []);
        return (
            <div>
                <noscript
                    ref={ref}
                    dangerouslySetInnerHTML={{
                        __html: `${props.css ? `<style>${props.css}</style>` : ""}${props.html}`,
                    }}
                ></noscript>
            </div>
        );
    },
    (prevProps, newProps) => {
        return prevProps.html === newProps.html && prevProps.css === newProps.css;
    },
);

export default HtmlWidget;

/**
 * Try to pull an existing nonce out of the page.
 */
function lookupNonce(fallback: string): string {
    return document.querySelector("script")?.nonce ?? fallback;
}
