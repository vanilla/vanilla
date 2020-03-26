/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { borders, colorOut, unit, margins, userSelect } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { mixinClickInput } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const paginationCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const primary = colorOut(mainColors.primary);
    const primaryContrast = colorOut(mainColors.primaryContrast);

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
                backgroundColor: colorOut(globalVars.mainColors.fg.fade(0.05)),
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
                cursor: "default",
            },
            allStates: {
                backgroundColor: colorOut(globalVars.mixBgAndFg(0.1)),
                cursor: "default",
            },
        },
    );

    cssOut(`.Content .PageControls`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        flexDirection: "row-reverse",
        marginBottom: unit(16),
    });

    cssOut(`.MorePager`, {
        textAlign: "right",
        $nest: {
            "& a": {
                color: colorOut(globalVars.mainColors.primary),
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
};
