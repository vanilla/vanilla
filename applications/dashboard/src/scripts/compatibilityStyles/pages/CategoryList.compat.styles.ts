/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { categoryListVariables } from "@dashboard/compatibilityStyles/pages/CategoryList.variables";

export const categoryListCompatCSS = () => {
    const globalVars = globalVariables();
    const vars = categoryListVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "CategoryList");
};
