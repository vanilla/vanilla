/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { negative } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent } from "csx";
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
        ...{
            ".Navigation-row": {
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
            },
            ".NewDiscussion": {
                width: percent(100),
            },
            ".BoxButtons": {
                width: percent(100),
            },
            ".ButtonGroup.Multi": {
                maxWidth: percent(100),
            },
            ".Dropdown.MenuItems": {
                ...{
                    "&&": {
                        top: percent(100),
                    },
                },
            },

            ".ButtonGroup.NewDiscussion.Multi": {
                display: "flex",
                flexWrap: "wrap",
                ...{
                    ".Button.Primary": {
                        position: "relative",
                    },
                    ".Button.Handle": {
                        position: "relative",
                        width: styleUnit(formElVars.sizing.height),
                        height: styleUnit(formElVars.sizing.height),
                        marginLeft: styleUnit(negative(formElVars.sizing.height)),
                        borderTopLeftRadius: important(0),
                        borderBottomLeftRadius: important(0),
                    },
                    ".Dropdown.MenuItems": {
                        position: "relative",
                        maxWidth: percent(100),
                        marginTop: styleUnit(negative(formElVars.border.width)),
                    },
                    ".mobileFlyoutOverlay": {
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
        top: styleUnit(globalVars.gutter.half),
        right: styleUnit(globalVars.gutter.half),
        zIndex: 10,
    });

    const spacer = (count: number) => {
        const formElVars = formElementsVariables();
        return style("spacer", {
            height: styleUnit(1),
            width: count ? styleUnit(count * formElVars.sizing.height) : styleUnit(formElVars.sizing.height * 2),
        });
    };

    const container = style("container", {
        paddingBottom: globalVars.gutter.half,
    });

    return {
        root,
        content,
        spacer,
        container,
        closeButton,
    };
});
