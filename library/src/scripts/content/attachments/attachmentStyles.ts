/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, allLinkStates, borders, margins, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, px } from "csx";

export const attachmentVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("attachment");

    const padding = makeThemeVars("padding", {
        default: 12,
    });

    const text = makeThemeVars("text", {
        fontSize: globalVars.fonts.size.medium,
    });

    const title = makeThemeVars("title", {
        color: globalVars.mixBgAndFg(0.9),
    });

    const loading = makeThemeVars("loading", {
        opacity: 0.5,
    });

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
        padding: vars.padding.default,
        width: percent(100),
        ...borders({
            color: globalVars.elementaryColors.transparent,
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
