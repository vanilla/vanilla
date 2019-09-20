/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { embedContainerVariables } from "@library/embeddedContent/embedStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, paddings, margins, importantUnit, unit } from "@library/styles/styleHelpers";
import { percent } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const quoteEmbedClasses = useThemeCache(() => {
    const vars = globalVariables();
    const embedVars = embedContainerVariables();
    const style = styleFactory("quoteEmbed");

    const root = style({});

    const body = style("body", {
        display: "block",
        textAlign: "left",
        margin: 0,
        padding: 0,
        $nest: {
            "&::before, &::after": {
                content: "initial",
            },
        },
    });

    const userName = style("userName", {
        fontWeight: vars.fonts.weights.bold,
    });

    const header = style("header", {
        ...paddings({
            all: embedVars.spacing.padding,
            bottom: 0,
        }),
    });

    const content = style("content", {
        ...paddings({ all: embedVars.spacing.padding }),
    });

    const title = style("title", {
        ...lineHeightAdjustment(),
        ...margins({
            horizontal: importantUnit(0),
            top: importantUnit(0),
            bottom: importantUnit(vars.meta.spacing.default),
        }),
        display: "block",
        width: percent(100),
    });

    const titleLink = style("titleLink", {
        color: colorOut(vars.mainColors.fg),
        fontSize: vars.fonts.size.medium,
        fontWeight: vars.fonts.weights.bold,
    });

    return { root, body, userName, title, titleLink, header, content };
});
