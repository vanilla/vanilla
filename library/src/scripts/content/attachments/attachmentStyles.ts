/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    allLinkStates,
    borders,
    IBordersSameAllSidesStyles,
    margins,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { componentThemeVariables, styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, px } from "csx";
import { transparentColor } from "@library/forms/buttonStyles";

export const attachmentVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = componentThemeVariables("attachment");

    const padding = {
        default: 12,
        ...themeVars.subComponentStyles("padding"),
    };

    const text = {
        fontSize: globalVars.fonts.size.medium,
        ...themeVars.subComponentStyles("text"),
    };

    const title = {
        color: globalVars.mixBgAndFg(0.9),
        ...themeVars.subComponentStyles("title"),
    };

    const loading = {
        opacity: 0.5,
    };

    return { padding, text, title, loading };
});

export const attachmentClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const vars = attachmentVariables();
    const style = styleFactory("attachment");

    const link = style("link", {
        display: "block",
        width: "100%",
        ...allLinkStates({
            allStates: {
                textDecoration: "none",
            },
        }),
    });

    const box = style("box", {
        position: "relative",
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        width: percent(100),
        ...borders({
            color: transparentColor,
            width: 2,
            radius: 0,
        }),
    });

    const format = style("format", {
        flexBasis: px(globalVars.icon.sizes.small + vars.padding.default),
        height: unit(globalVars.icon.sizes.small),
        paddingRight: unit(vars.padding.default),
        flexShrink: 1,
    });

    const main = style("main", {
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexGrow: 1,
    });

    const title = style("title", {
        fontSize: px(vars.text.fontSize),
        color: vars.title.color.toString(),
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: px(globalVars.icon.sizes.small),
    });

    const metas = style("metas", {
        marginBottom: px(0),
        lineHeight: globalVars.lineHeights.condensed,
    });

    const close = style("close", {
        ...margins({
            top: px(-((formElementVars.sizing.height - globalVars.icon.sizes.default) / 2)),
            right: px(-((formElementVars.sizing.height - globalVars.icon.sizes.default) / 2)),
        }),
        pointerEvents: "all",
    });

    const loadingProgress = style("loadingProgress", {
        ...absolutePosition.bottomLeft(),
        transition: `width ease-out .2s`,
        height: px(3),
        marginBottom: px(0),
        width: 0,
        maxWidth: percent(100),
        backgroundColor: globalVars.mainColors.primary.toString(),
    });

    const loadingContent = style("loadingContent", {
        $nest: {
            [`.${format}`]: {
                opacity: vars.loading.opacity,
            },
            [`.${main}`]: {
                opacity: vars.loading.opacity,
            },
        },
    });

    return { link, box, format, main, title, metas, close, loadingProgress, loadingContent };
});
