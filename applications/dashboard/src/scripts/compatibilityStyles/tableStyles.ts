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
    importantUnit,
    margins,
    negative,
    paddings,
    pointerEvents,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const tableCSS = () => {
    const vars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = vars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(vars.meta.colors.fg);

    cssOut(
        `
        .Groups .DataTable.DiscussionsTable.DiscussionsTable td.DiscussionName,
        .DataTable.DiscussionsTable.DiscussionsTable td.DiscussionName
    `,
        {
            ...paddings({
                vertical: vars.gutter.size,
                horizontal: importantUnit(vars.gutter.half),
            }),
        },
    );

    cssOut(
        `.Groups .DataTable tbody td.LatestPost a, .Groups .DataTable tbody td.LastUser a, .Groups .DataTable tbody td.FirstUser a, .DataTable tbody td.LatestPost a, .DataTable tbody td.LastUser a, .DataTable tbody td.FirstUser a`,
        {
            color: colorOut(vars.mainColors.fg),
            fontSize: unit(vars.meta.text.fontSize),
            textDecoration: important("none"),
        },
    );
};
