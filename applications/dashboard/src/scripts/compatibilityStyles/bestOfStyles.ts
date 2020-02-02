/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const bestOfCSS = () => {
    const globalVars = globalVariables();

    cssOut(`body.Section-BestOf .Container .FilterMenu li.Active a`, {
        color: colorOut(globalVars.mainColors.fg),
        borderColor: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .Tile`, {
        backgroundColor: colorOut(globalVars.mainColors.bg),
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .Tile .Title, body.Section-BestOf .Tile .Title a`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .Tile .Author a, body.Section-BestOf .Item .Author a`, {
        color: colorOut(globalVars.mainColors.fg),
        fontSize: unit(globalVars.meta.text.fontSize),
    });

    cssOut(`body.Section-BestOf .Tile .Message`, {
        overflow: "auto",
    });
};
