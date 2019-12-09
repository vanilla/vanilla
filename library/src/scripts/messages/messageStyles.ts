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
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { relative } from "path";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const messagesVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("messages");

    const sizing = themeVars("sizing", {
        minHeight: 49,
        width: 900, // only applies to "fixed" style
    });

    const spacing = themeVars("spacing", {
        padding: {
            vertical: 12,
            left: 50,
            right: 25,
        },
    });
    const iconPadding = themeVars("iconPadding", {
        padding: {
            left: spacing.padding.right + 30,
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
        title,
        iconPadding,
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

    const wrap = (noIcon?: boolean) => {
        const padding = noIcon
            ? {
                  vertical: vars.spacing.padding.vertical,
                  left: vars.iconPadding.padding.left,
                  right: vars.spacing.padding.right,
              }
            : { ...vars.spacing.padding, right: vars.spacing.padding.right };
        return style("wrap", {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: "nowrap",
            minHeight: unit(vars.sizing.minHeight),
            width: percent(100),
            margin: "auto",
            color: colorOut(vars.colors.fg),
            ...paddings(padding),
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

    const noIcon = style("setPaddingLeft", {
        $nest: {
            "&&": {
                paddingLeft: unit(vars.spacing.padding.right),
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
        maxWidth: percent(100),
        position: "absolute",
        marginLeft: unit(-33),
        marginRight: unit(12),
        $nest: {
            "&&": {
                color: colorOut(globalVars.messageColors.error.fg),
            },
        },
    });
    const icon = style("icon", {
        top: unit(vars.spacing.padding.vertical),
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
        messageIcon,
        titleContent,
        content,
        confirm,
        errorIcon,
        messageWrapper,
        main,
        text,
        noIcon,
        icon,
        title,
    };
});
