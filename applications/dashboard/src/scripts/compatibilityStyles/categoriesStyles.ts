/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const categoriesCSS = () => {
    const globalVars = globalVariables();

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

    cssOut(`.categoryList-heading`, {
        color: colorOut(globalVars.mainColors.fg),
    });
};
