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
} from "@library/styles/styleHelpers";
import { percent, viewWidth } from "csx";
import { FontWeightProperty } from "csstype";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { titleBarVariables } from "@library/headers/titleBarStyles";

export const messagesVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("messages");

    const sizing = themeVars("sizing", {
        minHeight: 54,
        width: 900, // only applies to "fixed" style
    });

    const spacing = themeVars("spacing", {
        padding: {
            vertical: 8,
            horizontal: 42,
        },
    });

    const colors = themeVars("colors", {
        fg: globalVars.feedbackColors.warning.fg,
        bg: globalVars.feedbackColors.warning.bg,
        states: {
            fg: globalVars.feedbackColors.warning.state,
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
            horizontal: spacing.padding.horizontal / 2,
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
    const mediaQueries = layoutVariables().mediaQueries();

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

    const root = style(
        {
            width: percent(100),
        },
        margins({ horizontal: "auto" }),
    );

    const wrap = style(
        "wrap",
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
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
                right: vars.spacing.padding.horizontal / 2,
            }),
        },
        mediaQueries.xs({
            flexWrap: "wrap",
            paddingLeft: unit(vars.spacing.padding.horizontal / 2),
        }),
    );

    const message = style("message", {
        ...userSelect(),
        ...fonts(vars.text.font),
        flex: 1,
    });

    const setWidth = style("setWidth", {
        width: unit(vars.sizing.width),
        maxWidth: percent(100),
    });

    const actionButton = style(
        "actionButton",
        {
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
        mediaQueries.xs({
            padding: 0,
            width: percent(100),
            textAlign: "center",
        }),
    );

    return {
        root,
        wrap,
        actionButton,
        message,
        fixed,
        setWidth,
    };
});
