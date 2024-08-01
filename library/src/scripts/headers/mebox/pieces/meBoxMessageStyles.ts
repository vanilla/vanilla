/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { objectFitWithFallback, buttonStates, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, quote } from "csx";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";
import { StatusLightVariables } from "@library/statusLight/StatusLight.variables";

export const meBoxMessageVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("meBoxMessage");

    const statusLightVars = StatusLightVariables();

    const spacing = makeThemeVars("spacing", {
        padding: {
            top: 8,
            right: 12,
            bottom: 8,
            left: 12,
        },
    });

    const imageContainer = makeThemeVars("imageContainer", {
        width: 40,
    });

    const unreadDot = makeThemeVars("unreadDot", {
        width: statusLightVars.sizing.width,
    });

    return { spacing, imageContainer, unreadDot };
});

export const meBoxMessageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = meBoxMessageVariables();

    const root = css({
        display: "block",
        ...{
            "& + &": {
                borderTop: `solid 1px ${ColorsUtils.colorOut(globalVars.border.color)}`,
            },
        },
    });

    const link = css({
        ...userSelect(),
        display: "flex",
        flexWrap: "nowrap",
        color: "inherit",
        ...Mixins.padding(vars.spacing.padding),
        ...buttonStates({
            allStates: {
                textShadow: "none",
            },
            hover: {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
            },
            focus: {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
            },
            active: {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.active.highlight),
            },
        }),
    });

    const imageContainer = css({
        position: "relative",
        width: styleUnit(vars.imageContainer.width),
        height: styleUnit(vars.imageContainer.width),
        flexBasis: styleUnit(vars.imageContainer.width),
        borderRadius: percent(50),
        overflow: "hidden",
        border: `solid 1px ${globalVars.border.color.toString()}`,
    });

    const image = css({
        width: styleUnit(vars.imageContainer.width),
        height: styleUnit(vars.imageContainer.width),
        ...objectFitWithFallback(),
    });

    const status = css({
        flexBasis: styleUnit(vars.unreadDot.width),
    });

    const contents = css({
        flexGrow: 1,
        ...Mixins.padding({
            left: vars.spacing.padding.left,
            right: vars.spacing.padding.right,
        }),
        maxWidth: calc(`100% - ${styleUnit(vars.unreadDot.width + vars.imageContainer.width)}`),
    });

    const message = css({
        lineHeight: globalVars.lineHeights.excerpt,
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    return { root, link, imageContainer, image, status, contents, message };
});
