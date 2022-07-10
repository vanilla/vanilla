/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { homeWidgetContainerVariables } from "@library/homeWidget/HomeWidgetContainer.styles";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { getPixelNumber } from "@library/styles/styleUtils";
import clamp from "lodash/clamp";

export function getBreakPoints(sliderWrapperWidth: number, maxSlidesToShow?: number) {
    const itemSpacings = homeWidgetContainerVariables().itemSpacing.horizontal;
    const itemMinWidth = homeWidgetItemVariables().sizing.minWidth + getPixelNumber(itemSpacings, 16) * 2;
    const floored = Math.floor(sliderWrapperWidth / itemMinWidth);
    const actual = clamp(floored, 1, maxSlidesToShow ?? 5);
    return actual;
}
