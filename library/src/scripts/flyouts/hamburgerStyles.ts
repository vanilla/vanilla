/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    fonts,
    margins,
    paddings,
    buttonStates,
    unit,
    userSelect,
    negative,
} from "@library/styles/styleHelpers";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { important, percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { buttonResetMixin } from "@library/forms/buttonStyles";

export const hamburgerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("hamburger");

    const sizing = makeThemeVars("sizing", {});

    return {
        sizing,
    };
});

export const hamburgerClasses = useThemeCache(() => {
    const vars = hamburgerVariables();
    const globalVars = globalVariables();
    const style = styleFactory("hamburger");

    const root = style({});
    const content = style({
        paddingBottom: unit(9),
        $nest: {
            "& .Navigation-row": {
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
            },
            "& .NewDiscussion": {
                width: percent(100),
            },
            "& .BoxButtons": {
                width: percent(100),
            },
            "& .ButtonGroup.Multi": {
                maxWidth: percent(100),
            },
            "& .Dropdown.MenuItems": {
                $nest: {
                    "&&": {
                        top: percent(100),
                    },
                },
            },
        },
    });

    return {
        root,
        content,
    };
});
