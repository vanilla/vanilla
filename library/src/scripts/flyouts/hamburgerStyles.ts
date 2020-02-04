/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { negative, paddings, unit } from "@library/styles/styleHelpers";

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent } from "csx";
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
    const formElVars = formElementsVariables();
    const content = style({
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

            "& .ButtonGroup.NewDiscussion.Multi": {
                display: "flex",
                flexWrap: "wrap",
                $nest: {
                    "& .Button.Primary": {
                        position: "relative",
                    },
                    "& .Button.Handle": {
                        position: "relative",
                        width: unit(formElVars.sizing.height),
                        height: unit(formElVars.sizing.height),
                        marginLeft: unit(negative(formElVars.sizing.height)),
                    },
                    "& .Dropdown.MenuItems": {
                        position: "relative",
                        maxWidth: percent(100),
                        marginTop: unit(negative(formElVars.border.width)),
                    },
                    "& .mobileFlyoutOverlay": {
                        position: "relative",
                        height: "auto",
                        background: "none",
                    },
                },
            },
        },
    });

    const closeButton = style("closeButton", {
        position: "absolute",
        top: unit(globalVars.gutter.half),
        right: unit(globalVars.gutter.half),
        zIndex: 10,
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
        closeButton,
    };
});
