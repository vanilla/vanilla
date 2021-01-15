/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, color, rgba, px } from "csx";
import { defaultTransition, flexHelper, absolutePosition } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
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
            bg: ColorsUtils.offsetLightness(colors.overlayBg, 0.04),
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
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        height: styleUnit(vars.menuBar.height),
        display: "flex",
        paddingTop: styleUnit(vars.menuBar.padding.top + 2),
        paddingLeft: styleUnit(vars.menuBar.padding.horizontal - 3),
        position: "relative",
        zIndex: 1,
    });

    const menuBarDots = style("menuBarDots", {
        height: styleUnit(vars.menuBar.dotSize),
        width: styleUnit(vars.menuBar.dotSize),
        backgroundColor: "#bbb",
        borderRadius: percent(50),
        marginRight: styleUnit(3),
    });

    const actionButtons = style("actionButtons", {
        textAlign: "center",
        margin: "44px 0",
        paddingTop: styleUnit(vars.menuBar.height),
        ...flexHelper().middle(),
        flexDirection: "column",
    });

    const actionButton = style("actionButton", {
        marginBottom: styleUnit(globalVars.gutter.half),
        ...{
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
        backgroundColor: ColorsUtils.colorOut(vars.colors.overlayBg),
    });

    const wrapper = style("wrapper", {
        height: percent(100),
        display: "flex",
        flexDirection: "column",
    });

    const constraintContainer = style("constrainContainer", {
        maxWidth: styleUnit(vars.container.maxWidth),
        minWidth: styleUnit(vars.container.minWidth),
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
        borderRadius: styleUnit(2),
        ...{
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
        top: styleUnit(vars.menuBar.height),
    });

    const isFocused = style("isFocused", {
        ...{
            [`.${overlay}`]: {
                opacity: 1,
            },
        },
    });

    const previewImage = style("previewImage", {
        objectPosition: "center top",
        position: "absolute",
        top: styleUnit(vars.menuBar.height),
        left: 0,
        right: 0,
        bottom: 0,
        width: percent(100),
    });

    const actionDropdown = style("actionDropdown", {
        position: "absolute",
        top: styleUnit(vars.menuBar.height),
        right: styleUnit(vars.menuBar.height),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        ...{
            ".icon-dropDownMenu": {
                color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
            },
            "&.focus-visible": {
                borderRadius: "2px",
                backgroundColor: ColorsUtils.colorOut(vars.actionDropdown.state.bg),
            },
            "&:focus": {
                borderRadius: "2px",
                backgroundColor: ColorsUtils.colorOut(vars.actionDropdown.state.bg),
            },
            "&:hover": {
                borderRadius: "2px",
                backgroundColor: ColorsUtils.colorOut(vars.actionDropdown.state.bg),
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
        paddingBottom: styleUnit(4),
        paddingLeft: styleUnit(14),
        paddingRight: styleUnit(14),
        paddingTop: styleUnit(4),
        textAlign: "left",
        color: vars.colors.btnTextColor.toString(),
        ...{
            "&:hover": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
            },
            "&:focus": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
            },
            "&:active": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.active.highlight),
            },
        },
    });

    const action = style("dropDown-item", {
        ...{
            "&&:hover, &&:focus, &&active": {
                textDecoration: "none",
            },
        },
    });

    const title = style("title", {
        fontSize: globalVars.fonts.size.medium,
        fontWeight: globalVars.fonts.weights.semiBold,
        ...flexHelper().middleLeft(),
    });

    const titleIcon = style("titleIcon", {
        marginLeft: globalVars.gutter.half,
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
        title,
        titleIcon,
    };
});

export default themeCardClasses;
