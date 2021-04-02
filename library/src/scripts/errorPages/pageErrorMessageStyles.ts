/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { Mixins } from "@library/styles/Mixins";

export const pageErrorMessageClasses = () => {
    const style = styleFactory("pageErrorMessage");
    const globalVars = globalVariables();

    const root = style({
        justifyContent: "center",
    });

    const title = style("title", {
        fontSize: styleUnit(globalVars.fonts.size.title),
        lineHeight: globalVars.lineHeights.condensed,
        fontWeight: globalVars.fonts.weights.semiBold,
        textAlign: "center",
    });

    const description = style("description", {
        textAlign: "center",
        fontSize: styleUnit(globalVars.fonts.size.large),
        marginTop: styleUnit(12),
    });

    const cta = style("cta", {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        ...Mixins.margin({
            top: styleUnit(21),
            horizontal: "auto",
        }),
    });

    const titleAsParagraph = style("titleAsParagraph", {
        fontSize: globalVars.fonts.size.large,
    });

    const errorIcon = style("icon", {
        ...{
            "&&": {
                display: "block",
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                height: styleUnit(85),
                width: styleUnit(85),
                ...Mixins.margin({
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
