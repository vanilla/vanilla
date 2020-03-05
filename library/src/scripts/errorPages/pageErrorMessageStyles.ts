/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, margins, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";

export const pageErrorMessageClasses = () => {
    const style = styleFactory("pageErrorMessage");
    const globalVars = globalVariables();

    const root = style({
        justifyContent: "center",
    });

    const title = style("title", {
        fontSize: unit(globalVars.fonts.size.title),
        lineHeight: globalVars.lineHeights.condensed,
        fontWeight: globalVars.fonts.weights.semiBold,
        textAlign: "center",
    });

    const description = style("description", {
        textAlign: "center",
        fontSize: unit(globalVars.fonts.size.large),
        marginTop: unit(12),
    });

    const cta = style("cta", {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        ...margins({
            top: unit(21),
            horizontal: "auto",
        }),
    });

    const titleAsParagraph = style("titleAsParagraph", {
        fontSize: globalVars.fonts.size.large,
    });

    const errorIcon = style("icon", {
        $nest: {
            "&&": {
                display: "block",
                color: colorOut(globalVars.mainColors.primary),
                height: unit(85),
                width: unit(85),
                ...margins({
                    bottom: 12,
                    horizontal: "auto",
                }),
            },
        },
    });

    return {
        root,
        title,
        description,
        cta,
        titleAsParagraph,
        errorIcon,
    };
};
