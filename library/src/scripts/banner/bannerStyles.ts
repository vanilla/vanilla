/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { BackgroundColorProperty, FontWeightProperty, PaddingProperty, TextShadowProperty } from "csstype";
import { important, percent, px, quote, translateX, ColorHelper, url, rgba, calc, translateY } from "csx";
import {
    centeredBackgroundProps,
    fonts,
    IFont,
    unit,
    colorOut,
    backgroundHelper,
    absolutePosition,
    modifyColorBasedOnLightness,
    EMPTY_FONTS,
    EMPTY_SPACING,
    borders,
    EMPTY_BACKGROUND,
    EMPTY_BORDER,
} from "@library/styles/styleHelpers";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { compactSearchVariables, SearchBarButtonType } from "@library/headers/mebox/pieces/compactSearchStyles";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { media } from "typestyle";
import { containerVariables } from "@library/layout/components/containerStyles";

export enum BannerAlignment {
    LEFT = "left",
    CENTER = "center",
}

export const bannerVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory(["banner", "splash"]);
    const globalVars = globalVariables();
    const widgetVars = widgetVariables();
    const formElVars = formElementsVariables();

    const options = makeThemeVars("options", {
        alignment: BannerAlignment.CENTER,
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
            bottom: topPadding as PaddingProperty<TLength>,
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
        primaryContrast: globalVars.mainColors.primaryContrast,
        secondary: globalVars.mainColors.secondary,
        secondaryContrast: globalVars.mainColors.secondaryContrast,
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        borderColor: globalVars.mixPrimaryAndFg(0.4),
    });

    const state = makeThemeVars("state", {
        colors: {
            fg: colors.secondaryContrast,
            bg: colors.secondary,
        },
        borders: {
            color: colors.bg,
        },
        fonts: {
            color: colors.secondaryContrast,
        },
    });

    const border = {
        width: globalVars.border.width,
    };

    const backgrounds = makeThemeVars("backgrounds", {
        ...compactSearchVars.backgrounds,
    });

    const contentContainer = makeThemeVars("contentContainer", {
        minWidth: 550,
        padding: {
            ...spacing.padding,
            left: 0,
            right: 0,
        },
    });

    const imageElement = makeThemeVars("imageElement", {
        image: undefined as string | undefined,
        minWidth: 500,
        disappearingWidth: 500,
        padding: {
            ...EMPTY_SPACING,
            all: globalVars.gutter.size,
            right: 0,
        },
    });

    const outerBackground = makeThemeVars("outerBackground", {
        ...EMPTY_BACKGROUND,
        color: colors.primary.lighten("12%"),
        backgroundPosition: "50% 50%",
        backgroundSize: "cover",
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
        color: colors.primaryContrast,
        align: options.alignment,
        shadow: `0 1px 1px ${colorOut(
            modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(text.innerShadowOpacity),
        )}, 0 1px 25px ${colorOut(
            modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(text.outerShadowOpacity),
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
        },
        margins: {
            ...EMPTY_SPACING,
            top: 14,
            bottom: 12,
        },
        text: "How can we help you?",
    });

    const description = makeThemeVars("description", {
        text: undefined as string | undefined,
        font: {
            ...textMixin,
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

    const searchButtonOptions = makeThemeVars("searchButtonOptions", {
        type: SearchBarButtonType.TRANSPARENT,
    });

    const isTransparentButton = searchButtonOptions.type === SearchBarButtonType.TRANSPARENT;

    const bgColor = isTransparentButton ? rgba(0, 0, 0, 0) : colors.bg;
    const fgColor = isTransparentButton ? colors.primaryContrast : colors.fg;
    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const activeBorderColor = isTransparentButton ? colors.primaryContrast : colors.bg;

    const searchBar = makeThemeVars("searchBar", {
        sizing: {
            maxWidth: 705,
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
        shadow: {
            show: false,
            style: `0 1px 1px ${colorOut(
                modifyColorBasedOnLightness(colors.fg, text.shadowMix, true).fade(text.innerShadowOpacity),
            )}, 0 1px 25px ${colorOut(
                modifyColorBasedOnLightness(colors.fg, text.shadowMix, true).fade(text.outerShadowOpacity),
            )}` as TextShadowProperty,
        },
        border: {
            ...EMPTY_BORDER,
            color: colors.primaryContrast,
            leftColor: isTransparentButton ? colors.primaryContrast : colors.borderColor,
            radius: {
                left: globalVars.border.radius,
                right: 0,
            },
        },
    });

    const searchButton: IButtonType = makeThemeVars("bannerSearchButton", {
        name: "bannerSearchButton",
        spinnerColor: colors.primaryContrast,
        colors: {
            bg: bgColor,
            fg: fgColor,
        },
        borders: {
            ...(isTransparentButton
                ? {
                      color: colors.primaryContrast,
                      width: 1,
                  }
                : { color: colors.bg, width: 0 }),
            left: {
                color: searchBar.border.color,
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
        state: {
            colors: {
                fg: colors.primaryContrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.primaryContrast,
            },
        },
    } as IButtonType);

    const buttonShadow = makeThemeVars("shadow", {
        color: modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(0.05),
        full: `0 1px 15px ${colorOut(modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(0.3))}`,
        background: modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(
            0.1,
        ) as BackgroundColorProperty,
    });

    return {
        options,
        outerBackground,
        backgrounds,
        spacing,
        innerBackground,
        contentContainer,
        text,
        title,
        description,
        paragraph,
        searchBar,
        buttonShadow,
        searchButton,
        searchButtonOptions,
        colors,
        inputAndButton,
        imageElement,
        border,
        isTransparentButton,
    };
});

export const bannerClasses = useThemeCache(() => {
    const vars = bannerVariables();
    const style = styleFactory("banner");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const isCentered = vars.options.alignment === "center";
    const searchButton = style("searchButton", generateButtonStyleProperties(vars.searchButton), { left: -1 });

    const valueContainer = style("valueContainer", {
        $nest: {
            "&&": {
                ...borders(vars.searchBar.border),
                $nest: {
                    "&:active, &:hover, &:focus, &.focus-visible": {
                        ...borders(vars.searchBar.border),
                    },
                },
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

        return style("outerBackground", {
            position: "absolute",
            top: 0,
            left: 0,
            width: percent(100),
            height: calc(`100% + 2px`),
            transform: translateY(`-1px`), // Depending on how the browser rounds the pixels, there is sometimes a 1px gap above the banner
            ...centeredBackgroundProps(),
            display: "block",
            ...backgroundHelper(finalVars),
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

    const contentContainer = style(
        "contentContainer",
        {
            ...paddings(vars.contentContainer.padding),
            backgroundColor: vars.innerBackground.bg,
            minWidth: vars.contentContainer.minWidth,
        },
        media(
            {
                maxWidth: calc(
                    `${unit(vars.contentContainer.minWidth)} + ${unit(vars.contentContainer.padding.horizontal)} * 4`,
                ),
            },
            {
                width: percent(100),
                minWidth: "initial",
            },
        ),
        mediaQueries.oneColumnDown({
            ...paddings(vars.spacing.paddingMobile),
        }),
    );

    const text = style("text", {
        color: colorOut(vars.colors.primaryContrast),
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
                "& .search-results": {
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
                backgroundColor: colorOut(vars.buttonShadow.background),
                boxShadow: vars.buttonShadow.full,
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

    const descriptionWrap = style("descriptionWrap", { ...margins(vars.description.margins), ...textWrapMixin });

    const description = style("description", {
        display: "block",
        ...fonts(vars.description.font as IFont),
        flexGrow: 1,
    });

    const content = style("content", {
        borderColor: colorOut(vars.colors.primaryContrast),
        borderRadius:
            vars.searchButton.borders &&
            vars.searchButton.borders.right &&
            vars.searchButton.borders.right.radius &&
            (typeof vars.searchButton.borders.right.radius === "number" ||
                typeof vars.searchButton.borders.right.radius === "string")
                ? unit(vars.searchButton.borders.right.radius)
                : 0,
        zIndex: 1,
        boxShadow: vars.searchBar.shadow.show ? vars.searchBar.shadow.style : undefined,
        $nest: {
            "&.hasFocus .searchBar-valueContainer": {
                boxShadow: `0 0 0 1px ${colorOut(globalVars.mainColors.primary)} inset`,
            },
        },
    });

    const imagePositioner = style("imagePositioner", {
        display: "flex",
        flexDirection: "row",
        flexWrap: "nowrap",
        alignItems: "center",
    });

    const makeImageMinWidth = (rootUnit, padding) =>
        calc(
            `${unit(rootUnit)} - ${unit(vars.contentContainer.minWidth)} - ${unit(
                vars.contentContainer.padding.left ?? vars.contentContainer.padding.horizontal,
            )} - ${unit(padding)}`,
        );

    const imageElementContainer = style(
        "imageElementContainer",
        {
            alignSelf: "stretch",
            minWidth: makeImageMinWidth(globalVars.content.width, containerVariables().spacing.padding.horizontal),
            flexGrow: 1,
            position: "relative",
            overflow: "hidden",
        },
        media(
            { maxWidth: globalVars.content.width },
            {
                minWidth: makeImageMinWidth("100vw", containerVariables().spacing.padding.horizontal),
            },
        ),
        layoutVariables()
            .mediaQueries()
            .oneColumnDown({
                minWidth: makeImageMinWidth("100vw", containerVariables().spacing.paddingMobile.horizontal),
            }),
        media(
            { maxWidth: 500 },
            {
                display: "none",
            },
        ),
    );

    const imageElement = style(
        "imageElement",
        {
            ...absolutePosition.middleRightOfParent(),
            minWidth: unit(vars.imageElement.minWidth),
            ...paddings(vars.imageElement.padding),
            objectPosition: "100% 50%",
            objectFit: "contain",
            marginLeft: "auto",
            right: 0,
        },
        media(
            {
                maxWidth: calc(
                    `${unit(vars.imageElement.minWidth)} + ${unit(vars.contentContainer.minWidth)} + ${unit(
                        vars.imageElement.padding.horizontal ?? vars.imageElement.padding.all,
                    )} * 2`,
                ),
            },
            { right: "initial", objectPosition: "0% 50%" },
        ),
    );

    return {
        root,
        outerBackground,
        contentContainer,
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
        imageElementContainer,
        imageElement,
        imagePositioner,
    };
});
