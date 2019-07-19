/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes, media } from "typestyle";
import { ColorHelper, deg, percent, px, quote } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ContentProperty, DisplayProperty, PositionProperty } from "csstype";
import { absolutePosition, debugHelper, defaultTransition, unit } from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { layoutVariables, panelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { embedContainerVariables } from "@library/embeddedContent/embedStyles";

export const embedMenuClasses = () => {
    const style = styleFactory("imageEmbedMenu");
    const embedVars = embedContainerVariables();
    const globalVars = globalVariables();

    const mediaQueries = () => {
        const noRoomForMenuOnLeft = styles => {
            // Todo: figure out when to break on the menu position...
            return media(
                {
                    maxWidth: unit(
                        embedVars.dimensions.maxEmbedWidth + 2 * globalVars.buttonIcon.size + globalVars.gutter.half,
                    ),
                },
                styles,
            );
        };

        return {
            noRoomForMenuOnLeft,
        };
    };

    const root = style({
        marginRight: "auto",
    });

    const form = style("form", {
        display: "block",
        width: percent(100),
    });

    const imageContainer = style("imageContainer", {
        position: "relative",
    });

    return {
        root,
        form,
        imageContainer,
        mediaQueries,
    };
};
