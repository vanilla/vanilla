/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { mixinClickInput } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { important } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const paginationCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const primary = ColorsUtils.colorOut(mainColors.primary);

    mixinClickInput(
        `
        .Pager > span,
        .Pager > a,
    `,
        {},
        {
            default: {
                ...userSelect(),
            },
            allStates: {
                ...userSelect(),
                backgroundColor: ColorsUtils.colorOut(mainColors.fg.fade(0.05)),
            },
        },
    );

    mixinClickInput(
        `
        .Pager > a.Highlight
    `,
        {
            default: primary,
            allStates: primary,
        },
        {
            default: {
                backgroundColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
                cursor: "pointer",
                ...userSelect(),
            },
            allStates: {
                backgroundColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
                ...userSelect(),
            },
        },
    );

    cssOut(`.Pager .Next`, {
        borderTopRightRadius: globalVars.border.radius,
        borderBottomRightRadius: globalVars.border.radius,
    });

    cssOut(`.Pager .Previous`, {
        borderBottomLeftRadius: globalVars.border.radius,
        borderTopLeftRadius: globalVars.border.radius,
    });

    cssOut(`.Pager span`, {
        cursor: important("default"),
        backgroundColor: ColorsUtils.colorOut(mainColors.bg, {
            makeImportant: true,
        }),
        color: ColorsUtils.colorOut(globalVars.links.colors.default, {
            makeImportant: true,
        }),
        opacity: 0.5,
    });

    cssOut(`.Content .PageControls`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        marginBottom: styleUnit(16),
    });

    cssOut(`.MorePager`, {
        textAlign: "right",
        ...{
            "& a": {
                color: ColorsUtils.colorOut(mainColors.primary),
            },
        },
    });

    cssOut(`.PageControls-filters`, {
        flex: 1,
        display: "inline-flex",
        alignItems: "baseline",
    });

    cssOut(`.PageControls.PageControls .selectBox`, {
        height: "auto",
    });

    cssOut(`.Pager.NumberedPager > a.Highlight`, {
        color: ColorsUtils.colorOut(ColorsUtils.isLightColor(mainColors.fg) ? mainColors.fg.fade(0.85) : mainColors.fg),
        pointerEvents: "none",
        backgroundColor: ColorsUtils.colorOut(
            ColorsUtils.modifyColorBasedOnLightness({
                color: mainColors.bg,
                weight: 0.05,
            }),
        ),
    });
};
