/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, flexHelper, sticky, negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, viewHeight } from "csx";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";

export const compactMeBoxVariables = useThemeCache(() => {
    const themeVars = variableFactory("compactMeBox");
    const globalVars = globalVariables();

    const tab = themeVars("tab", {
        height: 44,
        width: 44,
    });

    const colors = themeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    return { tab, colors };
});

export const compactMeBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = compactMeBoxVariables();
    const style = styleFactory("compactMeBox");

    const root = style({
        display: "block",
        marginRight: negativeUnit((titleBarVariables().sizing.mobile.width - userPhotoVariables().sizing.small) / 2),
    });

    const openButton = style("openButton", {
        color: globalVars.elementaryColors.white.toString(),
    });

    const contents = style("contents", {
        position: "relative",
        height: percent(100),
    });

    const closeModal = style("closeModal", {
        ...{
            "&&": {
                ...absolutePosition.topRight(),
                width: styleUnit(vars.tab.width),
                height: styleUnit(vars.tab.height),
            },
        },
    });

    const tabList = style("tabList", sticky(), {
        top: 0,
        background: ColorsUtils.colorOut(vars.colors.bg),
        zIndex: 2,
        paddingRight: styleUnit(vars.tab.width),
        height: styleUnit(vars.tab.height),
        flexBasis: styleUnit(vars.tab.width),
        color: globalVars.mainColors.fg.toString(),
    });

    const tabButtonContent = style("tabButtonContent", {
        ...flexHelper().middle(),
        position: "relative",
        width: styleUnit(vars.tab.width),
        height: styleUnit(vars.tab.height),
    });

    const tabPanels = style("tabPanels", {
        height: calc(`100% - ${vars.tab.height}px`),
        position: "relative",
    });

    const tabButton = style("tabButton", {
        ...buttonResetMixin(),
        ...flexHelper().middle(),
    });

    const panel = style("panel", {
        maxHeight: percent(100),
        borderTop: 0,
        borderRadius: 0,
    });

    const body = style("body", {
        flexGrow: 1,
    });

    const scrollContainer = style("scrollContainer", {
        overflow: "auto",
    });

    return {
        root,
        openButton,
        contents,
        closeModal,
        tabList,
        tabPanels,
        tabButton,
        tabButtonContent,
        panel,
        body,
        scrollContainer,
    };
});
