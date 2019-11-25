/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    unit,
    userSelect,
    colorOut,
    paddings,
    borders,
    fonts,
    allButtonStates,
    margins,
    absolutePosition,
} from "@library/styles/styleHelpers";
import { percent, translate, viewWidth } from "csx";
import { FontWeightProperty } from "csstype";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { inherit } from "highlight.js";
import { relative } from "path";

export const messagesVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("messages");

    const sizing = themeVars("sizing", {
        minHeight: 49,
        width: 900, // only applies to "fixed" style
    });

    const spacing = themeVars("spacing", {
        padding: {
            vertical: 8,
            left: 50,
            right: 25,
        },
    });

    const colors = themeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
        bg: globalVars.messageColors.warning.bg,
        states: {
            fg: globalVars.messageColors.warning.state,
        },
    });

    const text = themeVars("text", {
        font: {
            color: colors.fg,
            size: globalVars.fonts.size.medium,
        },
    });

    const actionButton = themeVars("actionButton", {
        padding: {
            vertical: spacing.padding.vertical,
            horizontal: spacing.padding.right / 2,
        },
        font: {
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.bold as FontWeightProperty,
        },
        minHeight: 36,
    });

    return {
        sizing,
        spacing,
        colors,
        text,
        actionButton,
    };
});

export const messagesClasses = useThemeCache(() => {
    const vars = messagesVariables();
    const globalVars = globalVariables();
    const style = styleFactory("messages");
    const titleBarVars = titleBarVariables();
    const shadows = shadowHelper();

    const wrap = style("wrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        minHeight: unit(vars.sizing.minHeight),

        width: percent(100),

        margin: "auto",
        color: colorOut(vars.colors.fg),
        ...paddings({
            ...vars.spacing.padding,
            right: vars.spacing.padding.right,
        }),
    });

    // Fixed wrapper
    const fixed = style("fixed", {
        position: "fixed",
        left: 0,
        top: unit(titleBarVars.sizing.height - 8),
        minHeight: unit(vars.sizing.minHeight),
        maxWidth: percent(100),
        zIndex: 20,
        $nest: {
            [`& .${wrap}`]: {
                width: unit(950),
                maxWidth: percent(100),
            },
        },
    });

    const innerWrapper = style("innerWrapper", {
        $nest: {
            "&&": {
                flexDirection: "row",
            },
        },
    });
    const messageWrapper = style("messageWrapper", {
        position: "relative",
        display: "flex",
        paddingLeft: 30,
        alignItems: "center",
        flexDirection: "row",
        margin: "0 auto",
        paddingTop: 7,
        paddingBottom: 7,
    });

    const noPadding = style("noPadding", {
        $nest: {
            "&&": {
                top: 49,
                minHeight: 48,
            },
        },
    });

    const root = style({
        width: percent(100),
        backgroundColor: colorOut(vars.colors.bg),
        ...shadowOrBorderBasedOnLightness(
            globalVars.body.backgroundImage.color,
            borders({
                color: globalVars.mainColors.fg,
            }),
            shadows.embed(),
        ),
        ...margins({ horizontal: "auto" }),
        $nest: {
            "& + &": {
                marginTop: unit(globalVars.spacer.size),
            },
        },
    });

    const message = style("message", {
        ...userSelect(),
        ...fonts(vars.text.font),
        display: "flex",
        alignItems: "center",
        justifyContent: "flexStart",
        width: percent(100),
        flex: 1,
    });

    const setWidth = style("setWidth", {
        width: unit(vars.sizing.width),
        maxWidth: percent(100),
    });

    const actionButton = style("actionButton", {
        $nest: {
            "&&": {
                ...paddings(vars.actionButton.padding),
                minHeight: unit(vars.actionButton.minHeight),
                whiteSpace: "nowrap",
                ...fonts(vars.actionButton.font),
                ...allButtonStates({
                    noState: {
                        color: colorOut(vars.colors.fg),
                    },
                    allStates: {
                        color: colorOut(vars.colors.states.fg),
                    },
                    focusNotKeyboard: {
                        outline: 0,
                    },
                }),
            },
        },
    });

    const messageIcon = style("messageIcon", {
        ...absolutePosition.middleLeftOfParent(),
        maxWidth: percent(100),
        transform: translate(`-100%`),
        marginLeft: unit(-16),
        $nest: {
            "&&": {
                color: "#555A62", // hard coded to contrast "yellow"
            },
        },
    });

    const errorIcon = style("errorIcon", {
        $nest: {
            "&&": {
                color: colorOut(globalVars.mainColors.fg),
            },
        },
    });
    const content = style("content", {
        width: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        position: "relative",
    });

    const confirm = style("confirm", {});

    const main = style("main", {
        flexGrow: 1,
    });
    const text = style("text", {
        ...margins({
            top: unit(6),
            bottom: unit(0),
        }),
    });

    const title = style("title", {});

    return {
        root,
        wrap,
        actionButton,
        message,
        fixed,
        innerWrapper,
        setWidth,
        messageIcon,
        content,
        confirm,
        errorIcon,
        noPadding,
        messageWrapper,
        main,
        text,
        title,
    };
});
