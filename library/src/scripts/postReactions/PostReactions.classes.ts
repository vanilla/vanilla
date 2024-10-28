/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { media } from "@library/styles/styleShim";
import { useThemeCache } from "@library/styles/themeCache";

/**
 * Classes and styling for the reactions component
 */
export const postReactionsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        gap: globalVars.gutter.quarter,
    });

    const button = css({
        height: 20,
        display: "inline-flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "center",
        padding: 0,
    });

    const activeButton = css({
        background: ColorsUtils.colorOut(globalVars.mainColors.primary),
        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
        "&:hover, &:active, &:focus": {
            background: ColorsUtils.colorOut(globalVars.mainColors.statePrimary),
            borderColor: ColorsUtils.colorOut(globalVars.mainColors.statePrimary),
            color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
        },
    });

    const buttonLabel = css({
        ...Mixins.margin({ right: globalVars.gutter.quarter }),
    });

    const icon = css({
        height: 16,
    });

    const tooltip = css({
        display: "flex",
        flexDirection: "column",
        gap: globalVars.gutter.half,
    });

    const tooltipTitle = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        fontWeight: "bold",
        gap: globalVars.gutter.quarter,
    });

    const tooltipIcon = css({
        width: 16,
        height: 16,
    });

    const tooltipUserList = css({
        ...Mixins.margin({ all: 0 }),
        ...Mixins.padding({ all: 0 }),
        display: "flex",
        flexDirection: "column",
        alignItems: "stretch",
        gap: globalVars.gutter.half,
        "& > li": {
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "flex-start",
            gap: globalVars.gutter.half,
        },
    });

    return {
        root,
        button,
        activeButton,
        buttonLabel,
        icon,
        tooltip,
        tooltipTitle,
        tooltipIcon,
        tooltipUserList,
    };
});
