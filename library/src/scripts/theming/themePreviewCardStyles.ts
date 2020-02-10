/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, color, rgba, px } from "csx";
import {
    unit,
    paddings,
    defaultTransition,
    flexHelper,
    colorOut,
    emphasizeLightness,
    absolutePosition,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const themeCardVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themePreviewCard");

    const colors = makeThemeVars("colors", {
        fg: color("#adb2bb"),
        white: color("#ffffff"),
        imgColor: color("#0291db"),
        btnTextColor: color("#555a62"),
        overlayBg: rgba(0, 0, 0, 0.4),
    });

    const container = makeThemeVars("container", {
        maxWidth: 600,
        minWidth: 220,
        ratioHeight: 2,
        ratioWidth: 3,
    });

    const menuBar = makeThemeVars("menuBar", {
        height: 10,
        padding: {
            top: 0,
            horizontal: 10,
        },
        dotSize: 4,
    });

    const actionDropdown = makeThemeVars("actionDropdown", {
        state: {
            bg: emphasizeLightness(colors.overlayBg, 0.04),
        },
    });

    return {
        colors,
        container,
        menuBar,
        actionDropdown,
    };
});

export const themeCardClasses = useThemeCache(() => {
    const vars = themeCardVariables();
    const style = styleFactory("themePreviewCard");
    const globalVars = globalVariables();

    const menuBar = style("menuBar", {
        background: colorOut(globalVars.mainColors.bg),
        height: unit(vars.menuBar.height),
        display: "flex",
        paddingTop: unit(vars.menuBar.padding.top + 2),
        paddingLeft: unit(vars.menuBar.padding.horizontal - 3),
        position: "relative",
        zIndex: 1,
    });

    const menuBarDots = style("menuBarDots", {
        height: unit(vars.menuBar.dotSize),
        width: unit(vars.menuBar.dotSize),
        backgroundColor: "#bbb",
        borderRadius: percent(50),
        marginRight: unit(3),
    });

    const actionButtons = style("actionButtons", {
        textAlign: "center",
        margin: "44px 0",
        paddingTop: unit(vars.menuBar.height),
        ...flexHelper().middle(),
        flexDirection: "column",
    });

    const actionButton = style("actionButton", {
        marginBottom: unit(globalVars.gutter.half),
        $nest: {
            "&&": {
                minWidth: px(180),
            },
            "&:last-child": {
                marginBottom: 0,
            },
        },
    });

    const overlay = style("overlay", {
        ...absolutePosition.fullSizeOfParent(),
        opacity: 0,
        ...flexHelper().middle(),
        ...defaultTransition("opacity"),
    });

    const overlayBg = style("overlayBg", {
        ...absolutePosition.fullSizeOfParent(),
        backgroundColor: colorOut(vars.colors.overlayBg),
    });

    const wrapper = style("wrapper", {
        height: percent(100),
        display: "flex",
        flexDirection: "column",
    });

    const constraintContainer = style("constrainContainer", {
        maxWidth: unit(vars.container.maxWidth),
        minWidth: unit(vars.container.minWidth),
        maxHeight: (vars.container.maxWidth * vars.container.ratioHeight) / vars.container.ratioWidth,
        ...shadowHelper().embed(),
    });

    const ratioContainer = style("ratioContainer", {
        position: "relative",
        width: "auto",
        paddingTop: percent((vars.container.ratioHeight / vars.container.ratioWidth) * 100),
    });

    const container = style("container", {
        ...absolutePosition.fullSizeOfParent(),
        borderRadius: unit(2),

        $nest: {
            [`&:hover .${overlay}`]: {
                opacity: 1,
            },
            [`&.forceHover .${overlay}`]: {
                opacity: 1,
            },
            [`&:focus .${overlay}`]: {
                opacity: 1,
            },
        },
    });

    const previewContainer = style("container", {
        ...absolutePosition.fullSizeOfParent(),
        overflow: "hidden",
    });

    const svg = style("svg", {
        ...absolutePosition.fullSizeOfParent(),
        top: unit(vars.menuBar.height),
    });

    const isFocused = style("isFocused", {
        $nest: {
            [`.${overlay}`]: {
                opacity: 1,
            },
        },
    });

    const previewImage = style("previewImage", {
        objectPosition: "center top",
        position: "absolute",
        top: unit(vars.menuBar.height),
        left: 0,
        right: 0,
        bottom: 0,
        width: percent(100),
        height: percent(100),
    });

    const actionDropdown = style("actionDropdown", {
        position: "absolute",
        top: unit(vars.menuBar.height),
        right: unit(vars.menuBar.height),
        color: colorOut(globalVars.elementaryColors.white),
        $nest: {
            "& .icon-dropDownMenu": {
                color: colorOut(globalVars.elementaryColors.white),
            },
            "&.focus-visible": {
                borderRadius: "2px",
                backgroundColor: colorOut(vars.actionDropdown.state.bg),
            },
            "&:focus": {
                borderRadius: "2px",
                backgroundColor: colorOut(vars.actionDropdown.state.bg),
            },
            "&:hover": {
                borderRadius: "2px",
                backgroundColor: colorOut(vars.actionDropdown.state.bg),
            },
        },
    });

    const itemLabel = style("itemLabel", {
        display: "block",
        flexGrow: 1,
    });

    const toolTipBox = style("toolTipBox", {
        width: "20px",
        height: "20px",
    });

    const actionLink = style("actionLink", {
        textDecoration: "none",
        paddingBottom: unit(4),
        paddingLeft: unit(14),
        paddingRight: unit(14),
        paddingTop: unit(4),
        textAlign: "left",
        color: vars.colors.btnTextColor.toString(),
        $nest: {
            "&:hover": {
                backgroundColor: colorOut(globalVars.states.hover.highlight, true),
            },
            "&:focus": {
                backgroundColor: colorOut(globalVars.states.hover.highlight, true),
            },
            "&:active": {
                backgroundColor: colorOut(globalVars.states.active.highlight, true),
            },
        },
    });

    const action = style("dropDown-item", {
        $nest: {
            "&&:hover, &&:focus, &&active": {
                textDecoration: "none",
            },
        },
    });

    return {
        svg,
        menuBar,
        menuBarDots,
        ratioContainer,
        previewContainer,
        container,
        constraintContainer,
        actionButtons,
        actionButton,
        previewImage,
        wrapper,
        overlay,
        overlayBg,
        isFocused,
        actionDropdown,
        itemLabel,
        toolTipBox,
        actionLink,
        action,
    };
});

export default themeCardClasses;
