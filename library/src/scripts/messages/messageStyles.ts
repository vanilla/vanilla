/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { userSelect, allButtonStates, allLinkStates, negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { percent, translate, em } from "csx";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
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
            withoutIcon: 18,
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
        font: Variables.font({
            color: colors.fg,
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.normal,
            lineHeight: globalVars.lineHeights.condensed,
        }),
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
        font: Variables.font({
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.semiBold,
            color: globalVars.mainColors.fg,
        }),
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

    const hasIcon = style("hasIcon", {});

    const wrap = style("wrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        minHeight: styleUnit(vars.sizing.minHeight),
        width: percent(100),
        margin: "auto",
        color: ColorsUtils.colorOut(vars.colors.fg),
        ...Mixins.padding({
            vertical: vars.spacing.padding.vertical,
            left: vars.spacing.padding.withoutIcon * 1.5,
            right: vars.spacing.padding.withoutIcon,
        }),
        ...{
            [`&.${hasIcon}`]: {
                paddingLeft: vars.spacing.padding.withIcon,
            },
        },
    });

    const message = style("message", {
        ...userSelect(),
        ...Mixins.font(vars.text.font),
        width: percent(100),
        flex: 1,
        position: "relative",
        ...Mixins.padding({
            vertical: 6,
        }),
    });

    // Fixed wrapper
    const fixed = style(
        "fixed",
        {
            position: "fixed",
            left: 0,
            top: styleUnit(titleBarVars.sizing.height + 1),
            minHeight: styleUnit(vars.sizing.minHeight),
            maxWidth: percent(100),
            zIndex: 20,
            ...{
                [`.${wrap}`]: {
                    maxWidth: percent(100),
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: "auto",
                },
                [`.${message}`]: {
                    width: "auto",
                },
            },
        },
        mediaQueries.oneColumnDown({
            top: styleUnit(titleBarVars.sizing.mobile.height + 1),
        }),
    );
    const messageWrapper = style("messageWrapper", {
        position: "relative",
        display: "flex",
        paddingLeft: 30,
        alignItems: "center",
        flexDirection: "row",
        margin: "0 auto",
        paddingTop: styleUnit(vars.spacing.padding.vertical),
        paddingBottom: styleUnit(vars.spacing.padding.vertical),
    });

    const root = style({
        width: percent(100),
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        ...shadowOrBorderBasedOnLightness(
            globalVars.body.backgroundImage.color,
            Mixins.border({
                color: globalVars.mainColors.fg,
            }),
            shadows.embed(),
        ),
        ...Mixins.margin({ horizontal: "auto" }),
        ...{
            "& + &": {
                marginTop: styleUnit(globalVars.spacer.size / 2),
            },
        },
    });

    const setWidth = style("setWidth", {
        width: styleUnit(vars.sizing.width),
        maxWidth: percent(100),
    });

    const actionButtonPrimary = style("actionButtonPrimary", {});

    const actionButton = style("actionButton", {
        ...{
            "&&": {
                position: "relative",
                ...Mixins.padding(vars.actionButton.padding),
                marginLeft: vars.actionButton.padding.left,
                minHeight: styleUnit(vars.actionButton.minHeight),
                whiteSpace: "nowrap",
                ...Mixins.font(vars.actionButton.font),
                ...allButtonStates({
                    noState: {
                        color: ColorsUtils.colorOut(vars.colors.fg),
                    },
                    allStates: {
                        color: ColorsUtils.colorOut(vars.colors.states.fg),
                    },
                    clickFocus: {
                        outline: 0,
                    },
                }),
                [`&.${actionButtonPrimary}`]: {
                    fontWeight: globalVars.fonts.weights.bold,
                },
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
        transform: translate(negativeUnit(vars.spacing.padding.withIcon)),
        width: styleUnit(vars.spacing.padding.withIcon),
    });

    const icon = style("icon", {
        // top: unit(vars.spacing.padding.vertical),
    });

    const errorIcon = style("errorIcon", {
        ...{
            "&&": {
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
            },
        },
    });
    const content = style("content", {
        width: percent(100),
        position: "relative",
        ...{
            a: allLinkStates({
                noState: {
                    color: ColorsUtils.colorOut(vars.colors.fg),
                    textDecoration: "underline",
                },
                allStates: {
                    color: ColorsUtils.colorOut(vars.colors.states.fg),
                    textDecoration: "underline",
                },
            }),
        },
    });

    const inlineBlock = style("inlineBlock", {
        display: "inline-block",
    });

    const confirm = style("confirm", {});

    const main = style("main", {});

    const text = style("text", {
        ...Mixins.font(vars.text.font),
    });
    const titleContent = style("titleContent", {
        display: "flex",
        justifyContent: "start",
        position: "relative",
        ...{
            [`& + .${text}`]: {
                marginTop: styleUnit(vars.title.margin.top),
            },
        },
    });
    const title = style("title", {
        ...Mixins.font(vars.text.font),
        fontWeight: globalVars.fonts.weights.bold,
        ...lineHeightAdjustment({
            [`& + .${text}`]: {
                marginTop: styleUnit(vars.title.margin.top),
            },
        }),
    });

    return {
        root,
        wrap,
        actionButton,
        actionButtonPrimary,
        message,
        fixed,
        setWidth,
        iconPosition,
        titleContent,
        content,
        inlineBlock,
        confirm,
        errorIcon,
        messageWrapper,
        main,
        text,
        hasIcon,
        icon,
        title,
    };
});
