/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@vanilla/library/src/scripts/styles/styleUtils";
import { color, ColorHelper, percent } from "csx";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import {
    paddings,
    fontFamilyWithDefaults,
    unit,
    flexHelper,
    colorOut,
    borders,
    margins,
    setAllLinkColors,
} from "@vanilla/library/src/scripts/styles/styleHelpers";
import { PlaceholderType } from "@openapi-embed/embed/OpenApiEmbedPlaceholder";
import { NestedCSSProperties } from "typestyle/lib/types";
import { lineHeightAdjustment } from "@vanilla/library/src/scripts/styles/textUtils";
import { keyframes } from "typestyle";

export const openApiEmbedVariables = useThemeCache(() => {
    const makeVars = variableFactory("openApiEmbed");
    const globalVars = globalVariables();

    const loadingAnimation = keyframes({
        "0%": { opacity: 0.8 },
        "50%": { opacity: 1 },
        "100%": { opacity: 0.8 },
    });

    const colors = makeVars("colors", {
        get: color("#61AFFE"),
        post: color("#4ACB90"),
        delete: color("#F93F3E"),
    });

    const sizes = makeVars("sizes", {
        placeholder: {
            textHeight: 12,
            rowHeight: 50,
            title: {
                width: 100,
                height: 30,
            },
        },
        padding: globalVars.gutter.size,
    });

    return {
        colors,
        sizes,
        loadingAnimation,
    };
});

export const openApiEmbedPlaceholderClasses = useThemeCache((type: PlaceholderType) => {
    const style = styleFactory("openApiEmbedPlaceHolder");
    const vars = openApiEmbedVariables();
    const globalVars = globalVariables();
    const color = vars.colors[type];

    const root = style({
        ...paddings({ all: vars.sizes.padding }),
        textAlign: "left",
    });

    const name = style("name", {
        marginBottom: globalVars.gutter.quarter,
        $nest: lineHeightAdjustment(),
    });
    const linkStyle = setAllLinkColors();

    const url = style("url", {
        display: "block",
        fontFamily: fontFamilyWithDefaults([], { isMonospaced: true }),
        fontSize: globalVars.fonts.size.medium,
        marginBottom: unit(globalVars.gutter.size),
        color: colorOut(globalVars.meta.colors.fg),
        $nest: linkStyle.nested,
    });

    const placeholderRow = style("placeholderRow", {
        marginBottom: globalVars.gutter.half,
        height: vars.sizes.placeholder.rowHeight,
        display: "flex",
        alignItems: "flex-end",
        width: percent(100),
        ...paddings({ all: globalVars.gutter.half }),
        ...borders({ color: color, radius: 4 }),
        background: colorOut(color.lighten(0.3)),
        $nest: {
            "&.isAnimated": {
                animationName: vars.loadingAnimation,
                animationDuration: "4s",
                animationIterationCount: "infinite",
            },
            "&:last-child": {
                marginBottom: 0,
            },
        },
    });

    const placeholderTitle = style("placeholderTitle", {
        fontFamily: fontFamilyWithDefaults([], { isMonospaced: true }),
        textTransform: "uppercase",
        fontWeight: globalVars.fonts.weights.bold,
        letterSpacing: 0.5,
        height: unit(vars.sizes.placeholder.title.height),
        width: unit(vars.sizes.placeholder.title.width),
        background: colorOut(color),
        color: colorOut(globalVars.elementaryColors.white),
        borderRadius: 2,
        ...flexHelper().middle(),
        $nest: {
            "&.isGet": {
                background: colorOut(vars.colors.get),
            },
        },
    });

    const placeholderTextContainer = style("placeholderTextContainer", {
        display: "flex",
        flex: 1,
        alignItems: "flex-end",
    });

    const placeholderMixin: NestedCSSProperties = {
        height: unit(vars.sizes.placeholder.textHeight),
        borderRadius: 2,
        marginBottom: 1,
    };

    const placeholderText1 = style("placeholderText1" + type, {
        background: colorOut(color.lighten(0.2)),
        flex: 1,
        ...placeholderMixin,
        ...margins({ right: globalVars.gutter.half, left: globalVars.gutter.size }),
    });

    const placeholderText2 = style("placeholderText2" + type, {
        height: unit(vars.sizes.placeholder.textHeight),
        background: colorOut(color.lighten(0.1)),
        ...placeholderMixin,
        flex: 3,
    });

    return {
        root,
        name,
        url,
        placeholderRow,
        placeholderTextContainer,
        placeholderTitle,
        placeholderText1,
        placeholderText2,
    };
});

export const openApiEmbedClasses = useThemeCache((type: PlaceholderType) => {
    const style = styleFactory("openApiEmbed");
    const vars = openApiEmbedVariables();
    const globalVars = globalVariables();

    return {};
});
