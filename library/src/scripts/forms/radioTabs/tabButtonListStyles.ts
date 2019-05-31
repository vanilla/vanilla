/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { colorOut, defaultTransition } from "@library/styles/styleHelpers";
import { countClasses } from "@library/content/countStyles";
import { compactMeBoxVariables } from "@library/headers/mebox/pieces/compactMeBoxStyles";

export const tabButtonListClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("tabButtonList");
    const classesCount = countClasses();
    const compactMeBoxVars = compactMeBoxVariables();

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "stretch",
    });

    const button = style("button", {
        flexGrow: 1,
        color: colorOut(globalVars.mainColors.fg),
        $nest: {
            ".icon": {
                ...defaultTransition("opacity"),
                opacity: 0.8,
            },
            "&:hover": {
                color: colorOut(globalVars.mainColors.primary),
                $nest: {
                    ".icon": {
                        opacity: 1,
                    },
                },
            },
            "&:focus, &:active, &.focus-visible": {
                color: colorOut(globalVars.mainColors.primary),
            },
            [`& .${classesCount.text()}`]: {
                color: colorOut(compactMeBoxVars.colors.bg),
            },
        },
    });

    return { root, button };
});
