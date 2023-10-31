/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { categoryListVariables } from "@library/categoriesWidget/CategoryList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";

export const categoryListCompatCSS = () => {
    const globalVars = globalVariables();
    const vars = categoryListVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "CategoryList");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "CategoryList", ".Panel");
};
