/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, colorOut, fonts, importantUnit, margins, paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { shadowHelper } from "@vanilla/library/src/scripts/styles/shadowHelpers";
import { percent } from "csx";

export const embedMenuClasses = useThemeCache(() => {
    const style = styleFactory("embedMenu");
    const globalVars = globalVariables();

    const root = style({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        position: "absolute",
        top: 0,
        left: percent(50),
        ...margins({ horizontal: "auto" }),
        transform: "translate(-50%, -50%)",
        background: colorOut(globalVars.mainColors.bg),
        ...shadowHelper().dropDown(),
        ...borders(),
        zIndex: 100,
        ...paddings({
            vertical: 2,
            horizontal: 6,
        }),
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
        $nest: {
            "&&": {
                // Specificity required to override default label styles
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
            },
        },
    });
    const verticalPadding = style("verticalPadding", {
        ...paddings({
            vertical: unit(globalVars.gutter.half),
        }),
    });

    return {
        root,
        form,
        imageContainer,
        paragraph,
        verticalPadding,
    };
});
