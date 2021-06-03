/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";

export const badgesVariables = useThemeCache(() => {
    const global = globalVariables();
    /**
     * @varGroup contributionItems
     * @commonTitle ContributionItems
     * @description Variables affecting user contribution Items(badges, reactions)
     */
    const makeThemeVars = variableFactory("badges");

    /**
     * @varGroup contributionItems.sizing
     * @title Sizing
     */
    const sizing = makeThemeVars("sizing", {
        width: 40,
    });

    /**
     * @varGroup contributionItems.spacing
     * @title Spacing
     * @expand spacing
     */
    const spacing = makeThemeVars(
        "spacing",
        Variables.spacing({
            horizontal: 28,
            vertical: 16,
        }),
    );

    /**
     * @varGroup contributionItems.colors
     * @title Color
     */
    const colors = makeThemeVars("colors", {
        count: {
            /**
             * @var contributionItems.colors.count.background
             * @title Background
             * @description Choose the background color of count itemm.
             * @type string
             */
            background: ColorsUtils.colorOut("#808080"),
            /**
             * @var contributionItems.colors.count.background
             * @title Border Color
             * @description Choose the border color of count itemm.
             * @type string
             */
            borderColor: global.elementaryColors.black,
        },
    });

    /**
     * @varGroup contributionItems.fonts
     * @title Fonts
     */
    const fonts = makeThemeVars("fonts", {
        count: {
            /**
             * @var contributionItems.fonts.count.size
             * @title Font Size
             * @description Choose the font size of count item.
             * @type number
             */
            size: 12,
        },
    });

    return {
        sizing,
        spacing,
        colors,
        fonts,
    };
});
