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
    negative,
} from "@library/styles/styleHelpers";
import { percent, translate, translateX, translateY, viewWidth, em } from "csx";
import { FontWeightProperty } from "csstype";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { relative } from "path";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const messagesVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const themeVars = variableFactory("messages");

    const sizing = themeVars("sizing", {
        minHeight: 49,
        width: 900, // only applies to "fixed" style
    });

    const spacing = themeVars("spacing", {
        padding: {
            vertical: 8,
            withIcon: 44,
            withoutIcon: 25,
        },
    });

    const colors = themeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
        bg: globalVars.messageColors.warning.bg,
        states: {
            fg: globalVars.messageColors.warning.state,
        },
    });
    const title = themeVars("title", {
        margin: {
            top: 6,
        },
    });

    const text = themeVars("text", {
        font: {
            color: colors.fg,
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.normal as FontWeightProperty,
        },
    });

    const actionButton = themeVars("actionButton", {
        position: "relative",
        padding: {
            vertical: 0,
            left: spacing.padding.withoutIcon / 2,
            right: spacing.padding.withoutIcon / 2,
        },
        margin: {
            left: spacing.padding.withoutIcon / 2,
        },
        font: {
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.bold as FontWeightProperty,
            color: globalVars.mainColors.fg,
        },
        minHeight: formElVars.sizing.height,
    });

    return {
        sizing,
        spacing,
        colors,
        text,
        title,
        actionButton,
    };
});

export const messagesClasses = useThemeCache(() => {
    const vars = messagesVariables();
    const globalVars = globalVariables();
    const style = styleFactory("messages");
    const titleBarVars = titleBarVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const shadows = shadowHelper();

    const wrap = (hasIcon?: boolean) => {
        return style("wrap", {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: "nowrap",
            minHeight: unit(vars.sizing.minHeight),
            width: percent(100),
            margin: "auto",
            color: colorOut(vars.colors.fg),
            ...paddings({
                vertical: vars.spacing.padding.vertical,
                left: hasIcon ? vars.spacing.padding.withIcon : vars.spacing.padding.withoutIcon,
                right: vars.spacing.padding.withoutIcon,
            }),
        });
    };

    // Fixed wrapper
    const fixed = style(
        "fixed",
        {
            position: "fixed",
            left: 0,
            top: unit(titleBarVars.sizing.height + 1),
            minHeight: unit(vars.sizing.minHeight),
            maxWidth: percent(100),
            zIndex: 20,

            $nest: {
                [`& .${wrap}`]: {
                    width: unit(950),
                    maxWidth: percent(100),
                },
            },
        },
        mediaQueries.oneColumnDown({
            top: unit(titleBarVars.sizing.mobile.height + 1),
        }),
    );

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
        paddingTop: unit(vars.spacing.padding.vertical),
        paddingBottom: unit(vars.spacing.padding.vertical),
    });

    // const noIcon = style("setPaddingLeft", {
    //     $nest: {
    //         "&&": {
    //             paddingLeft: unit(vars.spacing.padding.withoutIcon),
    //         },
    //     },
    // });

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
                marginTop: unit(globalVars.spacer.size / 2),
            },
        },
    });

    const message = style("message", {
        ...userSelect(),
        ...fonts(vars.text.font),
        width: percent(100),
        flex: 1,
        position: "relative",
        ...paddings({
            vertical: 6,
        }),
    });

    const setWidth = style("setWidth", {
        width: unit(vars.sizing.width),
        maxWidth: percent(100),
    });

    const actionButton = style("actionButton", {
        $nest: {
            "&&": {
                position: "relative",
                ...paddings(vars.actionButton.padding),
                marginLeft: vars.actionButton.padding.left,
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

    const iconPosition = style("iconPosition", {
        position: "absolute",
        left: 0,
        top: 0,
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        height: percent(100),
        maxHeight: em(2),
        transform: translate(`${negative(unit(vars.spacing.padding.withIcon))}`),
        width: unit(vars.spacing.padding.withIcon),
        $nest: {
            "&&": {
                color: colorOut(globalVars.messageColors.error.fg),
            },
        },
    });

    const icon = style("icon", {
        // top: unit(vars.spacing.padding.vertical),
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

    const main = style("main", {});

    const text = style("text", {
        ...fonts(vars.text.font),
    });
    const titleContent = style("titleContent", {
        display: "flex",
        justifyContent: "start",
        position: "relative",
        $nest: {
            [`& + .${text}`]: {
                marginTop: unit(vars.title.margin.top),
            },
        },
    });
    const title = style("title", {
        ...fonts(vars.text.font),
        fontWeight: globalVars.fonts.weights.bold,
        $nest: lineHeightAdjustment({
            [`& + .${text}`]: {
                marginTop: unit(vars.title.margin.top),
            },
        }),
    });

    return {
        root,
        wrap,
        actionButton,
        message,
        fixed,
        innerWrapper,
        setWidth,
        iconPosition,
        titleContent,
        content,
        confirm,
        errorIcon,
        messageWrapper,
        main,
        text,
        // noIcon,
        icon,
        title,
    };
});
