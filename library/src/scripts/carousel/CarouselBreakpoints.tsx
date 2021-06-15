/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import clamp from "lodash/clamp";

export function getBreakPoints(sliderWrapperWidth: number, maxSlidesToShow?: number) {
    //review mumber of slides after adding negative margin to slideWarpper
    let currentBreakPoint;
    let minWidth = homeWidgetItemVariables().sizing.minWidth;

    const floored = Math.floor(sliderWrapperWidth / minWidth);
    const actual = clamp(floored, 1, maxSlidesToShow ?? 5);

    return actual;
}
