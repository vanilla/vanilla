/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

interface IProps {
    // Unsafe HTML. Never allow untrusted user input in here.
    html: string;
}

/**
 * Configurable widget for rendering arbitrary HTML.
 */
export const HtmlWidget = React.memo(
    function HtmlWidget(props: IProps) {
        return (
            <div
                dangerouslySetInnerHTML={{
                    __html: props.html,
                }}
            ></div>
        );
    },
    (prevProps, newProps) => {
        return prevProps.html === newProps.html;
    },
);
