/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { iconVariables } from "@library/icons/iconStyles";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { negative, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { important, px, translateY } from "csx";

const backLinkClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();
    const vars = {
        sizing: {
            height: globalVars.icon.sizes.default,
            width: (globalVars.icon.sizes.default * 12) / 21, // From SVG ratio
        },
        position: {
            verticalOffset: globalVars.fonts.alignment.headings.verticalOffsetForAdjacentElements,
        },
    };

    const mobileSize = mediaQueries.oneColumnDown({
        height: 20,
    });

    const root = css(
        {
            ...userSelect(),
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            overflow: "visible",
            height: styleUnit(vars.sizing.height),
            minWidth: styleUnit(vars.sizing.width),
            ...Mixins.margin({
                left: negative(24),
                right: globalVars.gutter.half,
            }),
            transform: translateY("-0.1em"),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.margin({
                left: 0,
            }),
        }),
        mobileSize,
    );

    const link = css({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
        color: "inherit",
        height: styleUnit(vars.sizing.height),
        ...{
            "&:hover, &:focus": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                outline: 0,
            },
        },
    });

    const inHeading = useThemeCache((fontSize?: number | null) => {
        if (fontSize) {
            return css({
                ...Mixins.absolute.topLeft("50%"),
                fontSize: styleUnit(fontSize),
                transform: `translateY(-50%)`,
                marginTop: styleUnit(vars.position.verticalOffset),
            });
        } else {
            return "";
        }
    });

    const label = css(
        {
            fontWeight: globalVars.fonts.weights.semiBold,
            whiteSpace: "nowrap",
            paddingLeft: px(12),
        },
        mediaQueries.xs(Mixins.absolute.srOnly()),
    );

    const icon = css(
        {
            height: styleUnit(vars.sizing.height),
            width: styleUnit(vars.sizing.width),
        },
        mobileSize,
    );

    return {
        root,
        link,
        label,
        icon,
        inHeading,
    };
});

export default backLinkClasses;
