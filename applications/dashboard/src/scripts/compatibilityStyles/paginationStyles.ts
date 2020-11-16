/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    colorOut,
    unit,
    userSelect,
    importantColorOut,
    modifyColorBasedOnLightness,
    isLightColor,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { mixinClickInput } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { important } from "csx";

export const paginationCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const primary = colorOut(mainColors.primary);

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
                backgroundColor: colorOut(mainColors.fg.fade(0.05)),
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
                backgroundColor: colorOut(globalVars.mixBgAndFg(0.1)),
                cursor: "pointer",
                ...userSelect(),
            },
            allStates: {
                backgroundColor: colorOut(globalVars.mixBgAndFg(0.1)),
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
        backgroundColor: importantColorOut(mainColors.bg),
        color: importantColorOut(globalVars.links.colors.default),
        opacity: 0.5,
    });

    cssOut(`.Content .PageControls`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        marginBottom: unit(16),
    });

    cssOut(`.MorePager`, {
        textAlign: "right",
        $nest: {
            "& a": {
                color: colorOut(mainColors.primary),
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
        color: colorOut(isLightColor(mainColors.fg) ? mainColors.fg.fade(0.85) : mainColors.fg),
        pointerEvents: "none",
        backgroundColor: colorOut(
            modifyColorBasedOnLightness({
                color: mainColors.bg,
                weight: 0.05,
            }),
        ),
    });
};
