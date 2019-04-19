/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, important, percent, viewHeight } from "csx";

export const frameVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("frame");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    });

    const sizing = makeThemeVars("sizing", {
        large: 720,
        medium: 516,
        small: 375,
    });

    const border = makeThemeVars("border", {
        radius: globalVars.border.radius,
    });

    const spacing = makeThemeVars("spacing", {
        padding: 16,
    });

    const header = makeThemeVars("header", {
        spacing: spacing.padding,
        minHeight: 44,
        fontSize: globalVars.fonts.size.subTitle,
    });

    const footer = makeThemeVars("footer", {
        spacing: spacing.padding,
        minHeight: header.minHeight,
    });

    return {
        colors,
        sizing,
        border,
        spacing,
        header,
        footer,
    };
});

export const frameClasses = useThemeCache(() => {
    const vars = frameVariables();
    const style = styleFactory("frame");

    const headerWrap = style("headerWrap", {
        background: colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });
    const bodyWrap = style("bodyWrap", {
        background: colorOut(vars.colors.bg),
    });
    const footerWrap = style("footerWrap", {
        background: colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });

    const root = style({
        backgroundColor: colorOut(vars.colors.bg),
        maxHeight: percent(100),
        height: percent(100),
        borderRadius: unit(vars.border.radius),
        width: percent(100),
        position: "relative",
        display: "flex",
        flexDirection: "column",
        $nest: {
            [`.${bodyWrap}`]: {
                flex: 1,
                overflow: "auto",
            },
        },
    });

    return { root, headerWrap, bodyWrap, footerWrap };
});

export const frameHeaderClasses = useThemeCache(() => {
    const vars = frameVariables();
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const style = styleFactory("frameHeader");

    const root = style({
        display: "flex",
        position: "relative",
        alignItems: "center",
        flexWrap: "nowrap",
        width: percent(100),
        color: colorOut(vars.colors.fg),
        zIndex: 1,
        borderBottom: singleBorder(),
        ...paddings({
            top: 4,
            right: vars.footer.spacing,
            bottom: 4,
            left: vars.footer.spacing,
        }),
        $nest: {
            "& .button + .button": {
                marginLeft: unit(12 - formElVars.border.width),
            },
        },
    });

    const backButton = style("backButton", {
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "center",
        alignItems: "flex-end",
        flexShrink: 1,
        marginLeft: unit(-6),
    });

    const heading = style("heading", {
        display: "flex",
        alignItems: "center",
        flexGrow: 1,
        margin: 0,
        textOverflow: "ellipsis",
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: unit(globalVars.fonts.size.large),
    });

    const left = style("left", {
        fontSize: unit(vars.header.fontSize),
    });

    const centred = style("centred", {
        textAlign: "center",
        textTransform: "uppercase",
        fontSize: unit(globalVars.fonts.size.small),
        color: colorOut(globalVars.mixBgAndFg(0.6)),
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const spacerWidth = globalVars.icon.sizes.large - (globalVars.gutter.half + globalVars.gutter.quarter);
    const leftSpacer = style("leftSpacer", {
        display: "block",
        height: unit(spacerWidth),
        flexBasis: unit(spacerWidth),
        width: unit(spacerWidth),
    });

    const action = style("action", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        flexShrink: 1,
        height: unit(formElVars.sizing.height),
        width: unit(spacerWidth),
        color: colorOut(vars.colors.fg),
        $nest: {
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:hover, &:focus, &.focus-visible": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    return {
        root,
        backButton,
        heading,
        left,
        centred,
        leftSpacer,
        action,
    };
});

export const frameBodyClasses = useThemeCache(() => {
    const vars = frameVariables();
    const globalVars = globalVariables();
    const style = styleFactory("frameBody");

    const root = style({
        position: "relative",
        ...paddings({
            left: vars.spacing.padding,
            right: vars.spacing.padding,
        }),
        $nest: {
            "&.isSelfPadded": {
                ...paddings({
                    left: 0,
                    right: 0,
                }),
            },
            "& > .inputBlock": {
                $nest: {
                    "&.isFirst": {
                        marginTop: unit(globalVars.gutter.half),
                    },
                    "&.isLast": {
                        marginBottom: unit(globalVars.gutter.half),
                    },
                },
            },
        },
    });

    const noContentMessage = style("noContentMessage", {
        ...paddings({
            top: vars.header.spacing * 2,
            right: vars.header.spacing,
            bottom: vars.header.spacing * 2,
            left: vars.header.spacing,
        }),
    });
    const contents = style("contents", {
        ...paddings({
            top: vars.spacing.padding,
            right: 0,
            bottom: vars.spacing.padding,
            left: 0,
        }),
        minHeight: unit(50),
    });
    return { root, noContentMessage, contents };
});

export const frameFooterClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("frameFooter");
    const vars = frameVariables();

    const root = style({
        display: "flex",
        minHeight: unit(vars.footer.minHeight),
        alignItems: "center",
        position: "relative",
        zIndex: 1,
        borderTop: singleBorder(),
        flexWrap: "wrap",
        justifyContent: "space-between",
        ...paddings({
            top: 0,
            bottom: 0,
            left: vars.footer.spacing,
            right: vars.footer.spacing,
        }),
    });

    const justifiedRight = style("justifiedRight", {
        $nest: {
            "&&": {
                justifyContent: "flex-end",
            },
        },
    });

    const markRead = style("markRead", {
        $nest: {
            "&.buttonAsText": {
                fontWeight: globalVars.fonts.weights.semiBold,
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const actionButton = style("actionButton", {
        marginLeft: unit(24),
    });

    const selfPadded = style({
        paddingLeft: important(0),
        paddingRight: important(0),
    });

    return { root, markRead, selfPadded, actionButton, justifiedRight };
});
