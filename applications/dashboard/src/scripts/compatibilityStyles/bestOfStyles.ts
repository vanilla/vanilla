/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { metasVariables } from "@library/metas/Metas.variables";

export const bestOfCSS = () => {
    const globalVars = globalVariables();

    cssOut(`body.Section-BestOf .Container .FilterMenu li.Active a`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        borderColor: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .Tile`, {
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .DataList .Title`, {
        marginBottom: globalVars.spacer.headingItem,
    });
};
