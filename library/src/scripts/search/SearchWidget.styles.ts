/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { bannerVariables } from "@library/banner/Banner.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";

/**
 * Inherit some variables from the banner
 */
export const searchClasses = useThemeCache((borderRadius?: number) => {
    const globalVars = globalVariables();
    const vars = bannerVariables();

    const { almostBlack, white } = globalVars.elementaryColors;

    const searchButtonHoverBg = ColorsUtils.modifyColorBasedOnLightness({
        color: globalVars.mainColors.primary,
        weight: 0.05,
        inverse: true,
    });

    const buttonInteractionOverride = {
        borders: {
            color: ColorsUtils.colorOut(vars.searchBar.border.color),

            left: {
                width: styleUnit(0),
                radius: styleUnit(0),
            },
            top: {
                width: styleUnit(0),
            },
            right: {
                width: styleUnit(0),
            },
            bottom: {
                width: styleUnit(0),
            },
        },
        colors: {
            ...vars.searchButton.colors,
            bg: searchButtonHoverBg,
        },
    };

    const searchButtonVars = {
        ...vars.searchButton,
        colors: {
            fg: ColorsUtils.isLightColor(searchButtonHoverBg) ? almostBlack : white,
            bg: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
        borders: {
            ...vars.searchButton.borders,
            top: {
                ...vars.searchButton.borders?.top,
                color: ColorsUtils.colorOut(vars.searchBar.border.color),
                ...(borderRadius && { radius: borderRadius }),
            },
            right: {
                ...vars.searchButton.borders?.right,
                color: ColorsUtils.colorOut(vars.searchBar.border.color),
                ...(borderRadius && { radius: borderRadius }),
            },
            bottom: {
                ...vars.searchButton.borders?.bottom,
                color: ColorsUtils.colorOut(vars.searchBar.border.color),
                ...(borderRadius && { radius: borderRadius }),
            },
        },
        hover: buttonInteractionOverride,
        active: buttonInteractionOverride,
        focus: buttonInteractionOverride,
        focusVisible: buttonInteractionOverride,
        focusAccessible: buttonInteractionOverride,
    };

    const searchButton = css(Mixins.button(searchButtonVars));

    const content = css({
        boxSizing: "border-box",
        flexGrow: 1,
        zIndex: 1,
        boxShadow: vars.searchBar.shadow.show ? vars.searchBar.shadow.style : undefined,
        minHeight: styleUnit(vars.searchBar.sizing.height),
    });

    return {
        searchButton,
        content,
    };
});
