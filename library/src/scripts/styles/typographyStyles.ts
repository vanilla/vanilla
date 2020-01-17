/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { color, em, percent } from "csx";
import { margins, paddings, unit } from "@library/styles/styleHelpers";
import { containerVariables } from "@library/layout/components/containerStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { NestedCSSProperties, NestedCSSSelectors } from "typestyle/lib/types";

export const typographyClasses = useThemeCache(() => {
    const style = styleFactory("typography");
    const globalVars = globalVariables();
    const vars = containerVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const largeTitle = style("largeTitle", {
        width: percent(100),
    });

    const pageTitle = style(
        "pageTitle",
        {
            fontSize: unit(globalVars.fonts.size.title),
            lineHeight: globalVars.lineHeights.condensed,
            transform: `translateX(${em(globalVars.fonts.alignment.headings.horizontal)})`,
            $nest: {
                ...lineHeightAdjustment(),
                [`&.${largeTitle}`]: {
                    fontSize: unit(globalVars.fonts.size.largeTitle),
                },
            },
        } as NestedCSSProperties,
        mediaQueries.oneColumnDown({
            fontSize: unit(globalVars.fonts.mobile.size.title),
            $nest: {
                [`&.${largeTitle}`]: {
                    fontSize: unit(globalVars.fonts.mobile.size.title),
                },
            },
        }),
    );

    const subTitle = style("subTitle", {
        fontSize: unit(globalVars.fonts.size.subTitle),
    });

    const componentSubTitle = style("componentSubTitle", {
        fontSize: unit(globalVars.fonts.size.large),
    });

    return {
        largeTitle,
        pageTitle,
        subTitle,
        componentSubTitle,
    };
});
