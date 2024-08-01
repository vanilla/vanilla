/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import React from "react";

export function HomeWidgetItemDefaultImage() {
    //we'll have 3 layers in the image, primary color will go in the middle and
    //and respectively darker color above and lighter color in the bottom
    const primaryColor = globalVariables().mainColors.primary;
    const darkerColor = primaryColor.darken(0.05);
    const lighterColor = primaryColor.lighten(0.05);
    return (
        <svg
            width="480px"
            height="240px"
            viewBox="0 0 480 240"
            preserveAspectRatio="none"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
            className={homeWidgetItemClasses().defaultImageSVG}
            aria-hidden="true"
        >
            <g stroke="none" strokeWidth="1" fill="none" fillRule="evenodd">
                <g fillRule="nonzero">
                    <rect fill={ColorsUtils.colorOut(primaryColor)} x="0" y="0" width="480" height="240"></rect>
                    <polygon fill={ColorsUtils.colorOut(darkerColor)} points="0 0 390.857143 0 0 155.142857"></polygon>
                    <polygon
                        fill={ColorsUtils.colorOut(lighterColor)}
                        points="480 85.7142857 480 240 89.1428571 240"
                    ></polygon>
                </g>
            </g>
        </svg>
    );
}
