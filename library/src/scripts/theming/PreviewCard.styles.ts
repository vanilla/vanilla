/*
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px } from "csx";
import { defaultTransition, flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { Mixins } from "@library/styles/Mixins";
import previewCardVariables from "@library/theming/PreviewCard.variables";
import { css } from "@emotion/css";

const previewCardClasses = useThemeCache(() => {
    const vars = previewCardVariables();
    const globalVars = globalVariables();

    const menuBar = css({
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        height: styleUnit(vars.menuBar.height),
        display: "flex",
        paddingTop: styleUnit(vars.menuBar.padding.top + 2),
        paddingLeft: styleUnit(vars.menuBar.padding.horizontal - 3),
        position: "relative",
        zIndex: 1,
    });

    const menuBarDots = css({
        height: styleUnit(vars.menuBar.dotSize),
        width: styleUnit(vars.menuBar.dotSize),
        backgroundColor: "#bbb",
        borderRadius: percent(50),
        marginRight: styleUnit(3),
    });

    const actionButtons = css({
        textAlign: "center",
        margin: "44px 0",
        paddingTop: styleUnit(vars.menuBar.height),
        ...flexHelper().middle(),
        flexDirection: "column",
    });

    const actionButton = css({
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

    const overlay = css({
        ...Mixins.absolute.fullSizeOfParent(),
        opacity: 0,
        ...flexHelper().middle(),
        ...defaultTransition("opacity"),
        zIndex: 3,
    });

    const overlayBg = css({
        ...Mixins.absolute.fullSizeOfParent(),
        backgroundColor: ColorsUtils.colorOut(vars.colors.overlayBg),
    });

    const wrapper = css({
        height: percent(100),
        display: "flex",
        flexDirection: "column",
    });

    const constraintContainer = css({
        maxWidth: styleUnit(vars.container.maxWidth),
        minWidth: styleUnit(vars.container.minWidth),
        maxHeight: (vars.container.maxWidth * vars.container.ratioHeight) / vars.container.ratioWidth,
        ...shadowHelper().embed(),
        borderRadius: styleUnit(2),
    });

    const constraintContainerActive = css({
        border: "2px solid #0291db",
    });

    const ratioContainer = css({
        position: "relative",
        width: "auto",
        paddingTop: percent((vars.container.ratioHeight / vars.container.ratioWidth) * 100),
    });

    const activeOverlay = css({
        ...Mixins.absolute.fullSizeOfParent(),
        backgroundColor: "rgba(103, 105, 109, 0.2)",
        opacity: 1,
        zIndex: 2,
    });

    const flagSizeAndPosition = css({
        fontSize: 11,
        position: "absolute",
        top: "11%",
        left: 0,
    });

    const container = css({
        ...Mixins.absolute.fullSizeOfParent(),
        ...{
            [`:hover, :focus`]: {
                [`.${overlay}`]: {
                    opacity: 1,
                },
            },
        },
    });

    const previewContainer = css({
        ...Mixins.absolute.fullSizeOfParent(),
        overflow: "hidden",
    });

    const svg = css({
        ...Mixins.absolute.fullSizeOfParent(),
        top: styleUnit(vars.menuBar.height),
    });

    const isFocused = css({
        ...{
            [`.${overlay}`]: {
                opacity: 1,
            },
        },
    });

    const previewImage = css({
        objectPosition: "center top",
        objectFit: "cover",
        ...Mixins.absolute.fullSizeOfParent(),
        top: styleUnit(vars.menuBar.height),
    });

    const actionDropdown = css({
        position: "absolute",
        top: styleUnit(vars.menuBar.height),
        right: styleUnit(vars.menuBar.height),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        ...{
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

    const actionDropdownButton = css({
        color: `${ColorsUtils.colorOut(globalVars.elementaryColors.white)} !important`,
    });

    const itemLabel = css({
        display: "block",
        flexGrow: 1,
    });

    const toolTipBox = css({
        width: "20px",
        height: "20px",
    });

    const actionLink = css({
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

    const action = css({
        ...{
            "&&:hover, &&:focus, &&active": {
                textDecoration: "none",
            },
        },
    });

    const title = css({
        "&&": {
            marginTop: 12,
            marginBottom: 0, //fighting admin.css
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
                lineHeight: 20 / 14,
            }),
        },
        ...flexHelper().middleLeft(),
    });

    const titleIcon = css({
        marginLeft: globalVars.gutter.half,
    });

    return {
        svg,
        menuBar,
        menuBarDots,
        ratioContainer,
        previewContainer,
        container,
        activeOverlay,
        flagSizeAndPosition,
        constraintContainer,
        constraintContainerActive,
        actionButtons,
        actionButton,
        previewImage,
        wrapper,
        overlay,
        overlayBg,
        isFocused,
        actionDropdown,
        actionDropdownButton,
        itemLabel,
        toolTipBox,
        actionLink,
        action,
        title,
        titleIcon,
    };
});

export default previewCardClasses;
