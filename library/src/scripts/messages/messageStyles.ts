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
            weight: globalVars.fonts.weights.semiBold as FontWeightProperty,
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

    // Fixed wrapper
    const fixed = style("fixed", {
        position: "fixed",
        left: 0,
        top: unit(titleBarVars.sizing.height - 8),
        minHeight: unit(vars.sizing.minHeight),
        width: percent(100),
        maxWidth: viewWidth(100),
        zIndex: 20,
    });

    const innerWrapper = style("innerWrapper", {
        $nest: {
            "&&": {
                flexDirection: "row",
                maxWidth: "inherit",
                width: "auto",
            },
        },
    });

    const noPadding = style("noPadding", {
        $nest: {
            "&&": {
                padding: 0,
                top: 49,
                minHeight: 48,
            },
        },
    });

    const root = style(
        {
            width: percent(100),
        },
        margins({ horizontal: "auto" }),
    );

    const wrap = style("wrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        minHeight: unit(vars.sizing.minHeight),
        backgroundColor: colorOut(vars.colors.bg),
        width: percent(100),
        ...shadowOrBorderBasedOnLightness(
            globalVars.body.backgroundImage.color,
            borders({
                color: globalVars.mainColors.fg,
            }),
            shadows.embed(),
        ),
        margin: "auto",
        color: colorOut(vars.colors.fg),
        ...paddings({
            ...vars.spacing.padding,
            right: vars.spacing.padding.right,
        }),
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
        marginLeft: unit(-14),
        $nest: {
            "&&": {
                color: colorOut(globalVars.messageColors.error.fg),
            },
        },
    });

    const errorIcon = style("errorIcon", {
        ...absolutePosition.middleLeftOfParent(),
        maxWidth: percent(100),
        transform: translate(`-100%`),
        marginLeft: 0,
        $nest: {
            "&&": {
                color: colorOut(globalVars.mainColors.fg),
            },
        },
    });
    const iconWrap = style("iconWrap", {
        position: "relative",
        width: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        paddingLeft: 10,
    });

    const confirm = style("confirm", {});

    return {
        root,
        wrap,
        actionButton,
        message,
        fixed,
        innerWrapper,
        setWidth,
        messageIcon,
        iconWrap,
        confirm,
        errorIcon,
        noPadding,
    };
});
