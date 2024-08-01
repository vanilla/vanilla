/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

/** @internal */
export function DropDownArrow(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6" width={8} {...props}>
            <title>▾</title>
            <polygon points="0 0 10 0 5 6 0 0" fill="currentColor" />
        </svg>
    );
}
