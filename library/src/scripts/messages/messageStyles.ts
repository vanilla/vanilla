import { componentThemeVariables, styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import {
    IBorderStyles,
    modifyColorBasedOnLightness,
    srOnly,
    unit,
    userSelect,
    colorOut,
    paddings,
    borders,
    allLinkStates,
    fonts,
    allButtonStates,
} from "@library/styles/styleHelpers";
import { percent, px } from "csx";
import { FontWeightProperty, TextAlignLastProperty, TextShadowProperty } from "csstype";
import { layoutVariables } from "@library/styles/layoutStyles";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";

/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const messagesVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("messages");

    const sizing = themeVars("sizing", {
        minHeight: 54,
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
            weight: globalVars.fonts.weights.semiBold,
        },
        paddingRight: spacing.padding.horizontal / 2,
    });

    const actionButton = themeVars("actionButton", {
        padding: {
            vertical: 8,
            horizontal: spacing.padding.horizontal / 2,
        },
        font: {
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.bold,
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
    const shadows = shadowHelper();

    const root = style({
        ...userSelect(),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        minHeight: unit(vars.sizing.minHeight),
        backgroundColor: colorOut(vars.colors.bg),
        color: colorOut(vars.colors.fg),
        ...paddings({
            ...vars.spacing.padding,
            right: vars.spacing.padding.horizontal / 2,
        }),
        ...shadowOrBorderBasedOnLightness(
            globalVars.body.backgroundImage.color,
            borders({
                color: globalVars.mainColors.fg,
            }),
            shadows.embed(),
        ),
    });

    const message = style("message", {
        ...fonts(vars.text.font),
        paddingRight: unit(vars.text.paddingRight),
        flexGrow: 1,
    });

    const actionButton = style("actionButton", {
        ...paddings(vars.actionButton.padding),
        minHeight: unit(vars.actionButton.minHeight),
        ...allLinkStates({
            allStates: vars.colors.fg,
            noState: vars.colors.states.fg,
        }),
        ...fonts(vars.actionButton.font),
        ...allButtonStates({
            allStates: {
                color: colorOut(vars.colors.states.fg),
            },
        }),
    });

    return {
        root,
        actionButton,
        message,
    };
});
