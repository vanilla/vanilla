/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpersColors";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const messagesCSS = () => {
    const globalVars = globalVariables();

    cssOut(`.DismissMessage`, {
        color: colorOut(globalVars.elementaryColors.black),
    });
};
