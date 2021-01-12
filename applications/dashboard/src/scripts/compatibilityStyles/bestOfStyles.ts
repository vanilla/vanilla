/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";

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

    cssOut(`body.Section-BestOf .Tile .Title, body.Section-BestOf .Tile .Title a`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`body.Section-BestOf .Tile .Message`, {
        overflow: "auto",
    });

    cssOut(
        `
    body.Section-BestOf .Tile .Author a,
    body.Section-BestOf .Item .Author a
    `,
        {
            color: ColorsUtils.colorOut(globalVars.links.colors.default),
            fontSize: styleUnit(globalVars.meta.text.size),
        },
    );

    cssOut(
        `
    body.Section-BestOf .Tile .Author a:hover,
    body.Section-BestOf .Item .Author a:hover,
    `,
        {
            color: ColorsUtils.colorOut(globalVars.links.colors.hover),
        },
    );

    cssOut(
        `
    body.Section-BestOf .Tile .Author a:focus,
    body.Section-BestOf .Item .Author a:focus,
    `,
        {
            color: ColorsUtils.colorOut(globalVars.links.colors.focus),
        },
    );

    cssOut(
        `
    body.Section-BestOf .Tile .Author a:active,
    body.Section-BestOf .Item .Author a:active
    `,
        {
            color: ColorsUtils.colorOut(globalVars.links.colors.active),
        },
    );
};
