/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { color, em, percent } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { containerVariables } from "@library/layout/components/containerStyles";
import { panelLayoutVariables } from "@library/layout/PanelLayout.variables";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const typographyClasses = useThemeCache(() => {
    const style = styleFactory("typography");
    const globalVars = globalVariables();
    const mediaQueries = panelLayoutVariables().mediaQueries();

    const sharedTitleStyle: CSSObject = {
        color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
    };

    const pageTitle = style(
        "pageTitle",
        {
            ...sharedTitleStyle,
            width: "100%",
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("title"),
                lineHeight: globalVars.lineHeights.condensed,
            }),
            transform: `translateX(${em(globalVars.fonts.alignment.headings.horizontalOffset)})`,
            margin: 0,
            ...lineHeightAdjustment(),
        },
        mediaQueries.oneColumnDown({
            fontSize: styleUnit(globalVars.fonts.mobile.size.title),
        }),
    );

    const largeTitle = style(
        "largeTitle",
        {
            ...sharedTitleStyle,
            width: "100%",
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("largeTitle"),
            }),
        },
        mediaQueries.oneColumnDown({
            fontSize: styleUnit(globalVars.fonts.mobile.size.largeTitle),
        }),
    );

    const subTitle = style("subTitle", {
        ...sharedTitleStyle,
        width: "100%",
        // fontSize: styleUnit(globalVars.fonts.size.title),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("title"), // FIXME: check if this is a mistake (seems that font size should be subtitle)
        }),
    });

    const componentSubTitle = style("componentSubTitle", {
        ...sharedTitleStyle,
        width: "100%",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("subTitle"),
        }),
    });

    return {
        largeTitle,
        pageTitle,
        subTitle,
        componentSubTitle,
    };
});
