/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { lineHeightAdjustment } from "@vanilla/library/src/scripts/styles/textUtils";

export const categoriesCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    // Category list

    cssOut(`.DataList .Item .Title`, {
        marginBottom: unit(4),
    });

    cssOut(`.ItemContent.Category`, {
        position: "relative",
    });

    cssOut(`.DataList .PhotoWrap, .MessageList .PhotoWrap`, {
        top: unit(2),
    });
};
