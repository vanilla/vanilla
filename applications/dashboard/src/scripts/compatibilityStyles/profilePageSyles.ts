/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const profilePageCSS = () => {
    const globalVars = globalVariables();

    cssOut(`body.Section-Profile .Gloss, body.Section-Profile .Profile-rank`, {
        color: colorOut(globalVars.mainColors.primary),
        borderColor: colorOut(globalVars.mainColors.primary),
    });
};
