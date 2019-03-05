/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, defaultTransition } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { style } from "typestyle";

export const tabButtonListClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const debug = debugHelper("tabButtonList");

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "stretch",
        ...debug.name(),
    });

    const button = style({
        flexGrow: 1,
        color: globalVars.mainColors.fg.toString(),
        $nest: {
            ".icon": {
                ...defaultTransition("opacity"),
                opacity: 0.8,
            },
            "&:hover": {
                color: globalVars.mainColors.primary.toString(),
                $nest: {
                    ".icon": {
                        opacity: 1,
                    },
                },
            },
            "&:focus, &:active, &.focus-visible": {
                color: globalVars.mainColors.primary.toString(),
            },
        },
        ...debug.name("button"),
    });

    return { root, button };
});
