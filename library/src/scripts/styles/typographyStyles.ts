/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { em } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { css, CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const typographyClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const sharedTitleStyle: CSSObject = {
        color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
    };

    const pageTitle = css(
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

    const largeTitle = css(
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

    const subTitle = css({
        ...sharedTitleStyle,
        width: "100%",
        // fontSize: styleUnit(globalVars.fonts.size.title),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("title"), // FIXME: check if this is a mistake (seems that font size should be subtitle)
        }),
    });

    const componentSubTitle = css({
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
