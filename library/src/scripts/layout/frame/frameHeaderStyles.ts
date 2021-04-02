/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { appearance, singleBorder, flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, em, percent } from "csx";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { Mixins } from "@library/styles/Mixins";

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
        color: ColorsUtils.colorOut(vars.colors.fg),
        zIndex: 1,
        borderBottom: singleBorder(),
        ...Mixins.padding({
            top: 4,
            right: vars.footer.spacing,
            bottom: 4,
            left: vars.footer.spacing,
        }),
        ...{
            ".button + .button": {
                marginLeft: styleUnit(12 - formElVars.border.width),
            },
        },
    });

    const rootBorderLess = style("rootBorderless", {
        borderBottom: "none",
    });

    const rootMinimal = style("rootMinimal", {
        display: "block",
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
        width: calc(`100% - ${styleUnit(formElVars.sizing.height)}`),
        flexBasis: calc(`100% - ${styleUnit(formElVars.sizing.height)}`),
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: styleUnit(globalVars.fonts.size.large),
    });

    const headingMinimal = style("headingMinimal", {
        ...flexHelper().middle(),
        ...Mixins.padding({ horizontal: 24 }),
        ...{
            "& *": {
                textTransform: "uppercase",
                fontSize: styleUnit(globalVars.fonts.size.small),
            },
        },
    });

    const left = style("left", {
        fontSize: styleUnit(vars.header.fontSize),
    });

    const centred = style("centred", {
        textAlign: "center",
        textTransform: "uppercase",
        fontSize: styleUnit(globalVars.fonts.size.small),
        color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.6)),
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const spacerWidth = globalVars.icon.sizes.large - (globalVars.gutter.half + globalVars.gutter.quarter);

    const leftSpacer = style("leftSpacer", {
        display: "block",
        height: styleUnit(spacerWidth),
        flexBasis: styleUnit(spacerWidth),
        width: styleUnit(spacerWidth),
    });

    const action = style("action", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        flexShrink: 1,
        height: styleUnit(formElVars.sizing.height),
        width: styleUnit(formElVars.sizing.height),
        flexBasis: styleUnit(formElVars.sizing.height),
        color: ColorsUtils.colorOut(vars.colors.fg),
        transform: `translateX(10px)`,
        marginLeft: "auto",
        ...{
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:hover, &:focus, &.focus-visible": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const backButtonIcon = style("backButtonIcon", {
        display: "block",
    });

    const close = style("close", {
        ...appearance(),
        cursor: "pointer",
        height: styleUnit(formElVars.sizing.height),
        width: styleUnit(formElVars.sizing.height),
        flexBasis: styleUnit(formElVars.sizing.height),
        padding: 0,
        border: 0,
    });

    const closeMinimal = style("closeMinimal", {
        color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.6)),
        position: "absolute",
        top: 0,
        bottom: 0,
        ...Mixins.margin({ vertical: "auto" }),
        right: styleUnit(6),
    });

    const categoryIcon = style("categoryIcon", {
        flexBasis: styleUnit(18),
        marginRight: 0,
        opacity: 0.8,
    });

    return {
        closeMinimal,
        root,
        rootMinimal,
        rootBorderLess,
        backButton,
        heading,
        left,
        centred,
        leftSpacer,
        action,
        backButtonIcon,
        close,
        categoryIcon,
        headingMinimal,
    };
});
