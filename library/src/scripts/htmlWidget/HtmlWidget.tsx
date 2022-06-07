/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { prepareShadowRoot } from "@vanilla/dom-utils";
import React, { useLayoutEffect, useRef } from "react";

interface IProps {
    name?: string;
    // Unsafe HTML. Never allow untrusted user input in here.
    html: string;
    css?: string;
}

/**
 * Configurable widget for rendering arbitrary HTML.
 */
export const HtmlWidget = React.memo(
    function HtmlWidget(props: IProps) {
        const ref = useRef<HTMLElement>(null);

        useLayoutEffect(() => {
            if (!ref.current) {
                return;
            }
            prepareShadowRoot(ref.current, true);
        }, []);
        return (
            <div>
                <noscript
                    ref={ref}
                    dangerouslySetInnerHTML={{
                        __html: `${props.css && `<style>${props.css}</style>`}${props.html}`,
                    }}
                ></noscript>
            </div>
        );
    },
    (prevProps, newProps) => {
        return prevProps.html === newProps.html && prevProps.css === newProps.css;
    },
);
