/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRaw } from "typestyle";
import {
    borders,
    colorOut,
    margins,
    negative,
    pointerEvents,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const ideaCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut(
        `
        .idea-counter-module .idea-counter-box
        `,
        {
            backgroundColor: colorOut(globalVars.mixBgAndFg(0.1)),
        },
    );

    cssOut(
        `
        .idea-counter-module .arrow::before,
        .idea-counter-module .arrow::after
    `,
        {
            borderColor: colorOut(globalVars.mixBgAndFg(0.75)),
        },
    );

    cssOut(
        `
        .idea-counter-module .uservote .arrow::before,
        .idea-counter-module .uservote .arrow::after
    `,
        {
            borderColor: colorOut(globalVars.mixPrimaryAndBg(0.2)),
        },
    );

    cssOut(`.idea-counter-module .score`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(
        `
        .Groups .DataTable.DiscussionsTable.DiscussionsTable .ItemIdea td.DiscussionName .Wrap,
        .DataTable.DiscussionsTable.DiscussionsTable .ItemIdea td.DiscussionName .Wrap`,
        {
            paddingLeft: unit(50),
        },
    );
};
