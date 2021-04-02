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
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("title", "semiBold"),
            lineHeight: globalVars.lineHeights.condensed,
            align: "center",
        }),
    });

    const description = style("description", {
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large"),
            align: "center",
        }),
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
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large"),
        }),
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
