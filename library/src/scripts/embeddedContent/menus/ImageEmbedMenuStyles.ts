/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes } from "typestyle";
import { ColorHelper, deg, percent, quote } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ContentProperty, DisplayProperty, PositionProperty } from "csstype";
import { absolutePosition, debugHelper, defaultTransition, unit } from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { layoutVariables, panelLayoutClasses } from "@library/layout/panelLayoutStyles";

export const imageEmbedMenuVariables = () => {};

export const imageEmbedMenuClasses = () => {
    const style = styleFactory("imageEmbedMenuClasses");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        ...absolutePosition.topLeft(),
        transform: `translateX(-100%)`,
        zIndex: 1,
    });

    return { root };
};
