/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { BackgroundColorProperty, FontWeightProperty, PaddingProperty, TextShadowProperty } from "csstype";
import { important, percent, px, quote, translateX, ColorHelper, url, rgba } from "csx";
import {
    centeredBackgroundProps,
    fonts,
    getBackgroundImage,
    IFont,
    unit,
    colorOut,
    background,
    absolutePosition,
    modifyColorBasedOnLightness,
    EMPTY_FONTS,
    EMPTY_SPACING,
    borders,
    IButtonStates,
} from "@library/styles/styleHelpers";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import generateButtonClass, { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { compactSearchVariables, SearchBarButtonType } from "@library/headers/mebox/pieces/compactSearchStyles";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";

export const bannerVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory(["banner", "splash"]);
    const globalVars = globalVariables();
    const widgetVars = widgetVariables();
    const formElVars = formElementsVariables();
    const layoutVars = layoutVariables();

    const options = makeThemeVars("options", {
        alignment: "center" as "left" | "center",
        imageType: "background" as "background" | "element",
        hideDesciption: false,
        hideSearch: false,
    });
    const compactSearchVars = compactSearchVariables();

    const topPadding = 69;
    const horizontalPadding = unit(
        widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter,
    ) as PaddingProperty<TLength>;
    const spacing = makeThemeVars("spacing", {
        padding: {
            ...EMPTY_SPACING,
            top: topPadding as PaddingProperty<TLength>,
            bottom: (topPadding * 0.8) as PaddingProperty<TLength>,
            horizontal: horizontalPadding,
        },
        paddingMobile: {
            ...EMPTY_SPACING,
            top: 0,
            bottom: globalVars.gutter.size,
            horizontal: horizontalPadding,
        },
    });

    const inputAndButton = makeThemeVars("inputAndButton", {
        borderRadius: compactSearchVars.inputAndButton.borderRadius,
    });

    // Main colors
    const colors = makeThemeVars("colors", {
        primary: globalVars.mainColors.primary,
        secondary: globalVars.mainColors.secondary,
        contrast: globalVars.elementaryColors.white,
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        borderColor: globalVars.mainColors.fg.fade(0.4),
    });

    const backgrounds = makeThemeVars("backgrounds", {
        ...compactSearchVars.backgrounds,
    });

    const imageElement = makeThemeVars("imageElement", {
        width: percent(60),
        padding: {
            ...EMPTY_SPACING,
            all: globalVars.gutter.size,
        },
    });

    const outerBackground = makeThemeVars("outerBackground", {
        color: colors.primary.lighten("12%"),
        backgroundPosition: "50% 50%",
        backgroundSize: "cover",
        image: undefined as undefined | string,
        fallbackImage: undefined as undefined | string,
    });

    const innerBackground = makeThemeVars("innerBackground", {
        bg: undefined,
        padding: {
            top: spacing.padding,
            right: spacing.padding,
            bottom: spacing.padding,
            left: spacing.padding,
        },
    });

    const text = makeThemeVars("text", {
        shadowMix: 1, // We want to get the most extreme lightness contrast with text color (i.e. black or white)
        innerShadowOpacity: 0.25,
        outerShadowOpacity: 0.75,
    });

    const textMixin = {
        ...EMPTY_FONTS,
        color: colors.contrast,
        align: options.alignment,
        shadow: `0 1px 1px ${colorOut(
            modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(text.innerShadowOpacity),
        )}, 0 1px 25px ${colorOut(
            modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(text.outerShadowOpacity),
        )}` as TextShadowProperty,
    };

    const title = makeThemeVars("title", {
        maxWidth: 700,
        font: {
            ...textMixin,
            size: globalVars.fonts.size.largeTitle,
            weight: globalVars.fonts.weights.semiBold as FontWeightProperty,
        },
        fontMobile: {
            ...textMixin,
            size: globalVars.fonts.size.title,
            weight: globalVars.fonts.weights.semiBold as FontWeightProperty,
        },
        margins: {
            ...EMPTY_SPACING,
            top: 24,
            bottom: 12,
        },
        text: "How can we help you?",
    });

    const description = makeThemeVars("description", {
        font: {
            ...textMixin,
            color: colors.contrast,
            size: globalVars.fonts.size.large,
        },
        maxWidth: 400,
        margins: {
            ...EMPTY_SPACING,
            bottom: 12,
        },
    });

    const paragraph = makeThemeVars("paragraph", {
        margin: ".4em",
        text: {
            size: 24,
            weight: 300,
        },
    });

    const searchButtonOptions = makeThemeVars("searchButtonOptions", { type: SearchBarButtonType.TRANSPARENT });
    const isTransparentButton = searchButtonOptions.type === SearchBarButtonType.TRANSPARENT;

    const searchBar = makeThemeVars("searchBar", {
        sizing: {
            maxWidth: options.alignment === "left" ? layoutVars.contentSizes.full / 2 : 705,
        },
        font: {
            color: colors.fg,
            size: formElVars.giantInput.fontSize,
        },
        margin: {
            ...EMPTY_SPACING,
            top: 24,
        },
        marginMobile: {
            ...EMPTY_SPACING,
            top: 16,
        },
        border: {
            color: colors.contrast,
            leftColor: isTransparentButton ? colors.contrast : colors.borderColor,
            width: globalVars.border.width,
            radius: {
                left: globalVars.border.radius,
                right: 0,
            },
        },
    });

    const shadow = makeThemeVars("shadow", {
        color: modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.05),
        full: `0 1px 15px ${colorOut(modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.3))}`,
        background: modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.1) as BackgroundColorProperty,
    });

    const bgColor = isTransparentButton ? "transparent" : colors.bg;
    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const fgColor = isTransparentButton ? colors.contrast : colors.fg;
    const activeBorderColor = isTransparentButton ? colors.contrast : colors.bg;
    const searchButton: IButtonType = makeThemeVars("splashSearchButton", {
        name: "splashSearchButton",
        spinnerColor: colors.contrast,
        colors: {
            fg: fgColor,
            bg: bgColor,
        },
        borders: {
            ...(isTransparentButton
                ? {
                      color: colors.contrast,
                      width: 1,
                  }
                : { color: colors.bg, width: 0 }),
            left: {
                color: searchBar.border.leftColor,
                width: searchBar.border.width,
            },
            right: {
                radius: globalVars.border.radius,
            },
        },
        fonts: {
            ...searchBar.font,
            color: fgColor,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
        },
        hover: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        active: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        focus: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        focusAccessible: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
    });

    return {
        options,
        outerBackground,
        backgrounds,
        spacing,
        innerBackground,
        text,
        title,
        description,
        paragraph,
        searchBar,
        shadow,
        searchButton,
        searchButtonOptions,
        colors,
        inputAndButton,
        imageElement,
    };
});

export const bannerClasses = useThemeCache(() => {
    const vars = bannerVariables();
    const style = styleFactory("banner");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const isCentered = vars.options.alignment === "center";
    const isImageBg = vars.options.imageType === "background";
    const searchButton = style("searchButton", generateButtonStyleProperties(vars.searchButton), { left: -1 });

    const valueContainer = style("valueContainer", {
        $nest: {
            "&&": {
                ...borders(vars.searchBar.border),
            },
            ".inputText": {
                borderColor: colorOut(vars.searchBar.border.color),
            },
            ".searchBar__control": {
                cursor: "text",
            },
        },
    } as NestedCSSProperties);

    const root = style({
        position: "relative",
        backgroundColor: colorOut(vars.outerBackground.color),
    });

    const outerBackground = (url?: string) => {
        const finalUrl = url ?? vars.outerBackground.image ?? undefined;
        const finalVars = {
            ...vars.outerBackground,
            image: finalUrl,
        };

        if (vars.options.imageType !== "background") {
            delete finalVars.image;
            delete finalVars.fallbackImage;
        }

        return style("outerBackground", {
            ...centeredBackgroundProps(),
            display: "block",
            ...absolutePosition.fullSizeOfParent(),
            ...background(finalVars),
        });
    };

    const defaultBannerSVG = style("defaultBannerSVG", {
        ...absolutePosition.fullSizeOfParent(),
    });

    const backgroundOverlay = style("backgroundOverlay", {
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
        background: colorOut(vars.backgrounds.overlayColor),
    });

    const innerContainer = style(
        "innerContainer",
        {
            ...paddings(vars.spacing.padding),
            backgroundColor: vars.innerBackground.bg,
        },
        mediaQueries.oneColumnDown({
            ...paddings(vars.spacing.paddingMobile),
        }),
    );

    const text = style("text", {
        color: colorOut(vars.colors.contrast),
    });

    const searchContainer = style(
        "searchContainer",
        {
            position: "relative",
            width: percent(100),
            maxWidth: unit(vars.searchBar.sizing.maxWidth),
            margin: isCentered ? "auto" : undefined,
            ...margins(vars.searchBar.margin),
            $nest: {
                ".search-results": {
                    width: percent(100),
                    maxWidth: unit(vars.searchBar.sizing.maxWidth),
                    margin: "auto",
                    zIndex: 2,
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...margins(vars.searchBar.marginMobile),
        }),
    );

    const icon = style("icon", {});
    const input = style("input", {});

    const buttonLoader = style("buttonLoader", {});

    const title = style(
        "title",
        {
            display: "block",
            ...fonts(vars.title.font),
            flexGrow: 1,
        },
        mediaQueries.oneColumnDown({
            ...fonts(vars.title.fontMobile),
        }),
    );

    const textWrapMixin: NestedCSSProperties = {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        maxWidth: unit(vars.searchBar.sizing.maxWidth),
        width: percent(100),
        marginLeft: isCentered ? "auto" : undefined,
        marginRight: isCentered ? "auto" : undefined,
        ...mediaQueries.oneColumnDown({
            maxWidth: percent(100),
        }),
    };

    const titleAction = style("titleAction", {});
    const titleWrap = style("titleWrap", { ...margins(vars.title.margins), ...textWrapMixin });

    const titleFlexSpacer = style("titleFlexSpacer", {
        display: isCentered ? "block" : "none",
        position: "relative",
        height: unit(formElementVars.sizing.height),
        width: unit(formElementVars.sizing.height),
        flexBasis: unit(formElementVars.sizing.height),
        transform: translateX(px(formElementVars.sizing.height - globalVars.icon.sizes.default / 2 - 13)),
        $nest: {
            ".searchBar-actionButton:after": {
                content: quote(""),
                ...absolutePosition.middleOfParent(),
                width: px(20),
                height: px(20),
                backgroundColor: colorOut(vars.shadow.background),
                boxShadow: vars.shadow.full,
            },
            ".searchBar-actionButton": {
                color: important("inherit"),
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                },
            },
            ".icon-compose": {
                zIndex: 1,
            },
        },
    });

    const widget = style("widget", {
        display: "block",
    });

    const descriptionWrap = style("descriptionWrap", { ...margins(vars.description.margins), ...textWrapMixin });

    const description = style("description", {
        display: "block",
        ...fonts(vars.description.font as IFont),
        flexGrow: 1,
    });

    const content = style("content", {
        $nest: {
            "&&.hasFocus .searchBar-valueContainer": {
                borderColor: colorOut(vars.colors.contrast),
                boxShadow: `0 0 0 ${unit(globalVars.border.width)} ${colorOut(vars.colors.primary)} inset`,
                zIndex: 1,
            },
        },
    });

    const imageElement = style(
        "imageElement",
        {
            position: "absolute",
            top: 0,
            right: 0,
            bottom: 0,
            width: unit(vars.imageElement.width),
            height: percent(100),
            objectFit: "contain",
            ...paddings(vars.imageElement.padding),
            objectPosition: "100% 50%",
        },
        mediaQueries.oneColumnDown({
            display: "none",
        }),
    );

    return {
        widget,
        root,
        outerBackground,
        innerContainer,
        text,
        icon,
        defaultBannerSVG,
        searchContainer,
        searchButton,
        input,
        buttonLoader,
        title,
        titleAction,
        titleFlexSpacer,
        titleWrap,
        description,
        descriptionWrap,
        content,
        valueContainer,
        backgroundOverlay,
        imageElement,
    };
});
