/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes, media } from "typestyle";
import { ColorHelper, deg, important, percent, px, quote } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ContentProperty, DisplayProperty, PositionProperty } from "csstype";
import {
    absolutePosition,
    debugHelper,
    defaultTransition,
    fonts,
    importantUnit,
    margins,
    paddings,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { layoutVariables, panelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { embedContainerVariables } from "@library/embeddedContent/embedStyles";

export const embedMenuClasses = () => {
    const style = styleFactory("imageEmbedMenu");
    const embedVars = embedContainerVariables();
    const globalVars = globalVariables();

    const mediaQueries = () => {
        const noRoomForMenuOnLeft = styles => {
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

    // Extra specific and defensive here because it's within userContent styles.
    const paragraph = style("paragraph", {
        ...paddings({
            all: 0,
            top: importantUnit(globalVars.gutter.quarter),
        }),
        ...fonts({
            weight: globalVars.fonts.weights.normal,
            lineHeight: globalVars.lineHeights.base,
            color: globalVars.meta.colors.fg,
            size: globalVars.fonts.size.medium,
            align: "left",
        }),
    });

    const verticalPadding = style("verticalPadding", {
        ...paddings({
            vertical: unit(globalVars.gutter.half),
        }),
    });

    const mediaQueriesEmbed = embedMenuClasses().mediaQueries();

    const menuTransformWithGutter = unit(globalVars.buttonIcon.size);
    const menuTransformWithoutGutter = unit((globalVars.buttonIcon.size - globalVars.icon.sizes.default) / 2);

    const embedMetaDataMenu = style(
        "embedMetaDataMenu",
        {
            ...absolutePosition.topLeft(),
            width: globalVars.buttonIcon.size,
            height: globalVars.buttonIcon.size,
            transform: `translateX(-${menuTransformWithGutter}) translateY(-${menuTransformWithoutGutter})`,
            zIndex: 1,
        },
        mediaQueriesEmbed.noRoomForMenuOnLeft({
            transform: `translateX(-${menuTransformWithoutGutter}) translateY(-${menuTransformWithGutter})`,
        }),
    );

    return {
        root,
        form,
        imageContainer,
        mediaQueries,
        paragraph,
        verticalPadding,
        embedMetaDataMenu,
    };
};
