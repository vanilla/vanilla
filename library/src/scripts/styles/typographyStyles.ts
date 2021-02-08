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
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const typographyClasses = useThemeCache(() => {
    const style = styleFactory("typography");
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const pageTitle = style(
        "pageTitle",
        {
            fontSize: styleUnit(globalVars.fonts.size.title),
            fontWeight: globalVars.fonts.sizeWeight.title ?? undefined,
            lineHeight: globalVars.lineHeights.condensed,
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
            width: percent(100),
            fontSize: styleUnit(globalVars.fonts.size.largeTitle),
        },
        mediaQueries.oneColumnDown({
            fontSize: styleUnit(globalVars.fonts.mobile.size.title),
        }),
    );

    const subTitle = style("subTitle", {
        fontSize: styleUnit(globalVars.fonts.size.subTitle),
    });

    const componentSubTitle = style("componentSubTitle", {
        fontSize: styleUnit(globalVars.fonts.size.large),
    });

    return {
        largeTitle,
        pageTitle,
        subTitle,
        componentSubTitle,
    };
});
