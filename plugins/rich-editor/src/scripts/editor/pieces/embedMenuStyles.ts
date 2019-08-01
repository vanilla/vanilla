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
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { embedContainerVariables } from "@library/embeddedContent/embedStyles";
import { imageEmbedMenuVariables } from "@rich-editor/editor/pieces/richEditorEmedOptionsStyles";

export const embedMenuMediaQueries = useThemeCache(() => {
    const vars = embedContainerVariables();
    const globalVars = globalVariables();
    const noRoomForMenuOnLeft = styles => {
        return media(
            {
                maxWidth: unit(vars.dimensions.maxEmbedWidth + 2 * globalVars.buttonIcon.size + globalVars.gutter.half),
            },
            styles,
        );
    };

    return {
        noRoomForMenuOnLeft,
    };
});

export const embedMenuClasses = useThemeCache(() => {
    const style = styleFactory("imageEmbedMenu");
    const embedVars = embedContainerVariables();
    const globalVars = globalVariables();

    const root = style({
        marginRight: "auto",
    });

    const form = style("form", {
        display: "block",
        width: percent(100),
    });

    const mediaQueriesEmbed = embedMenuMediaQueries();

    const imageContainer = style(
        "imageContainer",
        {
            position: "relative",
            $nest: {
                "&:hover::after": {
                    display: "none", // Hide the box shadow
                },
            },
        },
        mediaQueriesEmbed.noRoomForMenuOnLeft({
            marginTop: unit(globalVars.buttonIcon.size),
        }),
    );

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
        paragraph,
        verticalPadding,
        embedMetaDataMenu,
    };
});
