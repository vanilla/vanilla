/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, CSSObject } from "@emotion/css";
import { IPageHeadingBoxOptions, pageHeadingBoxVariables } from "@library/layout/PageHeadingBox.variables";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { getPixelNumber } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const pageHeadingBoxClasses = useThemeCache((optionOverrides?: Partial<IPageHeadingBoxOptions>) => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();
    const vars = pageHeadingBoxVariables(optionOverrides);

    const excludeHeadingMargins: CSSObject = {
        "& h1, & h2, & h3, & h4, & h5, & h6": {
            // The title wrap provides the margin. No need for heading to ever provide it.
            // We fihgt some very specific styles in PageBox.compat.styles
            marginBottom: "0 !important",
        },
    };

    const root = css({
        textAlign: vars.options.alignment as "left",
        display: "flex", // Prevent margin collapse in here.
        flexDirection: "column",
        ...Mixins.margin({
            bottom: globalVars.spacer.headingBox,
        }),
    });
    const titleWrap = css(
        {
            width: "100%",
            ...Mixins.margin({
                bottom: globalVars.spacer.headingItem,
            }),
        },
        excludeHeadingMargins,
    );
    const descriptionWrap = css({
        width: "100%",
        ...Mixins.margin({
            bottom: globalVars.spacer.headingItem,
        }),
    });

    const subtitle = css(
        {
            width: "100%",
            ...Mixins.font(vars.subtitle.font),
            ...Mixins.margin({
                bottom: getPixelNumber(globalVars.spacer.headingItem) * 1.5,
            }),
        },
        excludeHeadingMargins,
    );

    const titleCount = css({
        whiteSpace: "nowrap", // prevents count value from stacking.
        textAlign: "right",
        paddingLeft: vars.font.letterSpacing,
        ...Mixins.font(vars.count),
    });

    return {
        root,
        titleWrap,
        descriptionWrap,
        subtitle,
        titleCount,
    };
});
