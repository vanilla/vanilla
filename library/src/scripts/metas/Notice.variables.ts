/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Variables } from "@library/styles/Variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { px } from "csx";

export const noticeVariables = useThemeCache(() => {
    /**
     * @varGroup notice
     * @description Variables affecting notices throughout the application.
     */
    const makeThemeVars = variableFactory("notice");

    const globalVars = globalVariables();
    const metasVars = metasVariables();

    /**
     * @varGroup notice.font
     * @title Font
     * @description Adjust the font values for notices.
     * @expand font
     */
    const fontInit = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("extraSmall", "semiBold"),
            color: globalVars.mainColors.primary,
        }),
    );

    /**
     * @varGroup notice.border
     * @description Adjust the border values of notices
     * @expand border
     */
    const border = makeThemeVars(
        "border",
        Variables.border({
            color: fontInit.color,
            width: globalVars.border.width,
            radius: 2,
        }),
    );

    // ensure the notice's total height is equal to that of other meta items
    const lineHeight = px(
        (metasVars.font.size! as number) * (metasVars.font.lineHeight! as number) - (border.width! as number) * 2,
    );

    const font = makeThemeVars(
        "font",
        Variables.font({
            ...fontInit,
            lineHeight,
        }),
    );

    /**
     * @varGroup notice.spacing
     * @description Adjust the inner spacing of notices
     * @expand border
     */
    const spacing = makeThemeVars("spacing", Variables.spacing({ horizontal: 5 }));

    return {
        font,
        border,
        spacing,
    };
});
