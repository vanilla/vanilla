/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, allLinkStates, negative } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, px } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";

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
        textAlign: "start",
        padding: vars.padding.default,
        // Offset the padding on the bottom a little bit so that the bottom padding looks event with the top.
        // The line-height causes them to look uneven without a little offset.
        paddingBottom: vars.padding.default - 4,
        width: percent(100),
        ...Mixins.border({
            color: globalVars.elementaryColors.transparent,
            width: 2,
            radius: 0,
        }),
    });

    const format = style("format", {
        flexBasis: px(globalVars.icon.sizes.small + vars.padding.default),
        height: styleUnit(globalVars.icon.sizes.small),
        paddingRight: styleUnit(vars.padding.default),
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
        ...lineHeightAdjustment(),
        fontSize: px(vars.text.fontSize),
        color: vars.title.color.toString(),
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: px(globalVars.icon.sizes.small),
    });

    const metas = style("metas", {
        ...Mixins.margin({
            left: styleUnit(negative(vars.padding.default / 2)),
            right: styleUnit(negative(vars.padding.default / 2)),
            bottom: 0,
        }),
        lineHeight: globalVars.lineHeights.condensed,
    });

    const close = style("close", {
        ...Mixins.margin({
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
        ...{
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
