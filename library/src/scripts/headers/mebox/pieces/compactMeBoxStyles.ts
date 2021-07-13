/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { flexHelper, sticky, negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, viewHeight } from "csx";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";

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

    const root = css({
        display: "block",
        marginRight: negativeUnit((titleBarVariables().sizing.mobile.width - userPhotoVariables().sizing.small) / 2),
    });

    const openButton = css({
        color: globalVars.elementaryColors.white.toString(),
    });

    const contents = css({
        position: "relative",
        height: percent(100),
    });

    const closeModal = css({
        ...{
            "&&": {
                ...Mixins.absolute.topRight(),
                width: styleUnit(vars.tab.width),
                height: styleUnit(vars.tab.height),
            },
        },
    });

    const tabList = css(sticky(), {
        top: 0,
        background: ColorsUtils.colorOut(vars.colors.bg),
        zIndex: 2,
        paddingRight: styleUnit(vars.tab.width),
        height: styleUnit(vars.tab.height),
        flexBasis: styleUnit(vars.tab.width),
        color: globalVars.mainColors.fg.toString(),
    });

    const tabButtonContent = css({
        ...flexHelper().middle(),
        position: "relative",
        width: styleUnit(vars.tab.width),
        height: styleUnit(vars.tab.height),
    });

    const tabPanels = css({
        height: calc(`100% - ${vars.tab.height}px`),
        position: "relative",
    });

    const tabButton = css({
        ...buttonResetMixin(),
        ...flexHelper().middle(),
    });

    const panel = css({
        maxHeight: percent(100),
        borderTop: 0,
        borderRadius: 0,
    });

    const body = css({
        flexGrow: 1,
    });

    const scrollContainer = css({
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
