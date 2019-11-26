/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings, unit } from "@library/styles/styleHelpers";

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { formElementsVariables } from "@library/forms/formElementStyles";

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
        ...paddings({
            vertical: 9,
        }),
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

    const spacer = (count: number) => {
        const formElVars = formElementsVariables();
        return style("spacer", {
            height: unit(1),
            width: count ? unit(count * formElVars.sizing.height) : unit(formElVars.sizing.height * 2),
        });
    };

    return {
        root,
        content,
        spacer,
    };
});
