/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

export const DEEP_LINK_ATTR = "data-deep-link";

/**
 * Override of the swagger DeepLink component.
 * @see https://github.com/swagger-api/swagger-ui/blob/master/src/core/components/deep-link.jsx
 */
export function VanillaSwaggerDeepLink({ enabled, path, text }: IProps) {
    const id = enabled ? `/${path}` : undefined;

    return (
        <a
            href={id}
            onClick={e => {
                e.preventDefault();
            }}
            className="nostyle"
            data-id={`/${path}`} // Force to always show this.
            data-deep-link={true}
        >
            <span>{text}</span>
        </a>
    );
}

interface IProps {
    enabled: boolean;
    path: string;
    text: string;
}
