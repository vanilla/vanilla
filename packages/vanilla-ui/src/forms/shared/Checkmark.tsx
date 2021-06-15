/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

/** @internal */
export function Checkmark(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" {...props} width={24}>
            <g fill="none" fillRule="evenodd">
                <g fill="#037DBC">
                    <g>
                        <g>
                            <path
                                d="M7.5 11.5L6 13 10 17 19 8.5 17.5 7 10 14z"
                                transform="translate(-1280.000000, -2642.000000) translate(946.000000, 2367.000000) translate(334.000000, 275.000000)"
                            />
                        </g>
                    </g>
                </g>
            </g>
        </svg>
    );
}
