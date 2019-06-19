/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { appearance, colorOut, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, percent } from "csx";
import { frameVariables } from "@library/layout/frame/frameStyles";

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
        transform: `translateX(-6px) translateY(-1px)`,
    });

    const heading = style("heading", {
        display: "flex",
        alignItems: "center",
        flexGrow: 1,
        margin: 0,
        textOverflow: "ellipsis",
        width: calc(`100% - ${formElVars.sizing.height}`),
        flexBasis: calc(`100% - ${formElVars.sizing.height}`),
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
        width: unit(formElVars.sizing.height),
        flexBasis: unit(formElVars.sizing.height),
        color: colorOut(vars.colors.fg),
        transform: `translateX(10px)`,
        $nest: {
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:hover, &:focus, &.focus-visible": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const backButtonIcon = style("backButtonIcon", {
        display: "block",
    });

    const close = style("close", {
        ...appearance(),
        cursor: "pointer",
        height: unit(formElVars.sizing.height),
        width: unit(formElVars.sizing.height),
        flexBasis: unit(formElVars.sizing.height),
        padding: 0,
        border: 0,
    });

    return {
        root,
        backButton,
        heading,
        left,
        centred,
        leftSpacer,
        action,
        backButtonIcon,
        close,
    };
});
