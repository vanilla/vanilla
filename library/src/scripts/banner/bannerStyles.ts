/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { searchBarClasses, searchBarVariables } from "@library/features/search/searchBarStyles";
import { ButtonPreset, buttonVariables } from "@library/forms/buttonStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { compactSearchVariables } from "@library/headers/mebox/pieces/compactSearchStyles";
import { containerVariables } from "@library/layout/components/containerStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    backgroundHelper,
    borderRadii,
    borders,
    colorOut,
    EMPTY_BACKGROUND,
    EMPTY_BORDER,
    EMPTY_FONTS,
    EMPTY_SPACING,
    fonts,
    IFont,
    importantUnit,
    isLightColor,
    modifyColorBasedOnLightness,
    negative,
    textInputSizingFromFixedHeight,
    unit,
    unitIfDefined,
} from "@library/styles/styleHelpers";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { BackgroundColorProperty, FontWeightProperty, PaddingProperty, TextShadowProperty } from "csstype";
import { calc, important, percent, px, quote, rgba, translateX, translateY, ColorHelper } from "csx";
import { media } from "typestyle";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";

export enum BannerAlignment {
    LEFT = "left",
    CENTER = "center",
}

export enum SearchBarPresets {
    NO_BORDER = "no border",
    BORDER = "border",
    UNIFIED_BORDER = "unified border", // wraps button, and will set button to "solid"
}

export type SearchPlacement = "middle" | "bottom";

export const bannerVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory(["banner", "splash"], forcedVars);
    const globalVars = globalVariables(forcedVars);
    const widgetVars = widgetVariables(forcedVars);

    const options = makeThemeVars("options", {
        alignment: BannerAlignment.CENTER,
        hideDescription: false,
        hideTitle: false,
        hideSearch: false,
        searchPlacement: "middle" as SearchPlacement,
    });
    const compactSearchVars = compactSearchVariables(forcedVars);

    const topPadding = 69;
    const horizontalPadding = unit(
        widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter,
    ) as PaddingProperty<TLength>;

    const spacing = makeThemeVars("spacing", {
        padding: {
            ...EMPTY_SPACING,
            top: topPadding as PaddingProperty<TLength>,
            bottom: 40 as PaddingProperty<TLength>,
            horizontal: horizontalPadding,
        },
        mobile: {
            padding: {
                ...EMPTY_SPACING,
                top: 0,
                bottom: globalVars.gutter.size,
                horizontal: horizontalPadding,
            },
        },
    });

    const dimensions = makeThemeVars("dimensions", {
        minHeight: 50,
        mobile: {
            minHeight: undefined as undefined | number | string,
        },
    });

    const inputAndButton = makeThemeVars("inputAndButton", {
        borderRadius: compactSearchVars.inputAndButton.borderRadius,
    });

    // Main colors
    const colors = makeThemeVars("colors", {
        primary: globalVars.mainColors.primary,
        primaryContrast: globalVars.mainColors.bg,
        secondary: globalVars.mainColors.secondary,
        secondaryContrast: globalVars.mainColors.secondaryContrast,
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        borderColor: globalVars.mixPrimaryAndFg(0.4),
    });

    const presets = makeThemeVars("presets", {
        button: { preset: isLightColor(colors.primaryContrast) ? ButtonPreset.TRANSPARENT : ButtonPreset.SOLID },
        input: { preset: SearchBarPresets.NO_BORDER },
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

    const border = makeThemeVars("border", {
        width: globalVars.border.width,
        radius: globalVars.borderType.formElements.default.radius,
    });

    const backgrounds = makeThemeVars("backgrounds", {
        ...compactSearchVars.backgrounds,
    });

    const contentContainer = makeThemeVars("contentContainer", {
        minWidth: 550,
        padding: {
            ...EMPTY_SPACING,
            top: spacing.padding.top,
            bottom: spacing.padding.bottom,
            horizontal: 0,
        },
        mobile: {
            padding: {
                top: 12,
                bottom: 12,
            },
        },
    });

    const rightImage = makeThemeVars("rightImage", {
        image: undefined as string | undefined,
        minWidth: 500,
        disappearingWidth: 500,
        padding: {
            ...EMPTY_SPACING,
            all: globalVars.gutter.size,
            right: 0,
        },
    });

    const logo = makeThemeVars("logo", {
        height: "auto" as number | string,
        width: 300 as number | string,
        padding: {
            all: 12,
        },
        image: undefined as string | undefined,
    });

    const outerBackground = makeThemeVars("outerBackground", {
        ...EMPTY_BACKGROUND,
        color: colors.primary.lighten("12%"),
        repeat: "no-repeat",
        position: "50% 50%",
        size: "cover",
        mobile: {
            image: undefined as undefined | string,
        },
    });

    const innerBackground = makeThemeVars("innerBackground", {
        ...EMPTY_BACKGROUND,
        unsetBackground: true,
        size: "unset",
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

    if (presets.input.preset === SearchBarPresets.UNIFIED_BORDER) {
        presets.button.preset = ButtonPreset.SOLID; // Unified border currently only supports solid buttons.
    }

    const isSolidButton = presets.button.preset === ButtonPreset.SOLID;
    const isTransparentButton = presets.button.preset === ButtonPreset.TRANSPARENT;

    const inputHasNoBorder =
        presets.input.preset === SearchBarPresets.UNIFIED_BORDER || presets.input.preset === SearchBarPresets.NO_BORDER;

    const searchBar = makeThemeVars("searchBar", {
        preset: presets.button.preset,
        colors: {
            fg: colors.fg,
            bg: colors.bg,
        },
        sizing: {
            maxWidth: 705,
            height: 40,
        },
        font: {
            color: colors.fg,
            size: globalVars.fonts.size.large,
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
            color: inputHasNoBorder ? colors.bg : colors.primary,
            leftColor: isTransparentButton ? colors.primaryContrast : colors.borderColor,
            radius: {
                left: border.radius,
                right: 0,
            },
            width: globalVars.border.width,
        },
        state: {
            border: {
                color: isSolidButton ? colors.fg : colors.primaryContrast,
            },
        },
    });

    let buttonBorderStyles = {
        color: colors.bg,
        width: 0,
        left: {
            ...EMPTY_BORDER,
            color: searchBar.border.color,
            width: searchBar.border.width,
            radius: 0,
        },
        right: {
            ...EMPTY_BORDER,
            radius: border.radius,
            color: colors.bg,
        },
    };

    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const activeBorderColor = isTransparentButton ? colors.primaryContrast : colors.bg;

    let buttonStateStyles = {
        colors: {
            fg: colors.secondaryContrast,
            bg: bgColorActive,
        },
        borders: {
            color: activeBorderColor,
        },
        fonts: {
            color: colors.primaryContrast,
        },
    };

    if (isTransparentButton) {
        buttonBorderStyles.color = colors.bg;
        buttonBorderStyles.width = globalVars.border.width;
    }

    const searchButtonBg = isTransparentButton ? rgba(0, 0, 0, 0) : colors.primary;

    const searchButton = makeThemeVars("searchButton", {
        name: "searchButton",
        preset: { style: presets.button.preset },
        spinnerColor: colors.primaryContrast,
        sizing: {
            minHeight: searchBar.sizing.height,
        },
        colors: {
            bg: searchButtonBg,
            fg: colors.bg,
        },
        borders: buttonBorderStyles,
        fonts: {
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.bold,
        },
        state: buttonStateStyles,
    } as IButtonType);

    if (isSolidButton) {
        const buttonVars = buttonVariables();
        searchButton.state = buttonVars.primary.state;
        searchButton.colors = buttonVars.primary.colors;
        searchButton.borders!.color = buttonVars.primary.borders.color;
    }

    const buttonShadow = makeThemeVars("shadow", {
        color: modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(0.05),
        full: `0 1px 15px ${colorOut(modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(0.3))}`,
        background: modifyColorBasedOnLightness(colors.primaryContrast, text.shadowMix).fade(
            0.1,
        ) as BackgroundColorProperty,
    });

    const unifiedBannerOptions = makeThemeVars("unifiedBannerOptions", {
        border: {
            width: 2,
            color: colors.secondary,
        },
    });

    const searchStrip = makeThemeVars("searchStrip", {
        bg: undefined as ColorHelper | undefined | string,
        minHeight: 60 as number | string,
        offset: undefined as number | string | undefined,
        padding: {
            top: 12,
            bottom: 12,
        },
        mobile: {
            bg: undefined as BackgroundColorProperty | undefined,
            minHeight: undefined as "string" | number | undefined,
            offset: undefined as "string" | number | undefined,
            padding: {
                ...EMPTY_SPACING,
            },
        },
    });

    return {
        presets,
        options,
        outerBackground,
        backgrounds,
        spacing,
        innerBackground,
        contentContainer,
        dimensions,
        text,
        title,
        description,
        paragraph,
        state,
        searchBar,
        buttonShadow,
        searchButton,
        colors,
        inputAndButton,
        rightImage,
        border,
        isTransparentButton,
        unifiedBannerOptions,
        searchStrip,
        logo,
    };
});

export const bannerClasses = useThemeCache(() => {
    const vars = bannerVariables();
    const { presets } = vars;
    const style = styleFactory("banner");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const isCentered = vars.options.alignment === "center";
    const searchButton = style("searchButton", {
        $nest: {
            "&.searchBar-submitButton": {
                ...generateButtonStyleProperties(vars.searchButton),
                borderTopRightRadius: importantUnit(vars.border.radius),
                borderBottomRightRadius: importantUnit(vars.border.radius),
                left: -1,
            },
        },
    });

    const valueContainer = mirrorLeftRadius => {
        return style("valueContainer", {
            $nest: {
                "&&.inputText": {
                    ...textInputSizingFromFixedHeight(
                        vars.searchBar.sizing.height,
                        vars.searchBar.font.size,
                        vars.searchBar.border.width * 2,
                    ),
                    boxSizing: "border-box",
                    paddingLeft: unit(searchBarVariables().searchIcon.gap),
                    backgroundColor: colorOut(vars.searchBar.colors.bg),
                    ...borders({
                        ...vars.searchBar.border,
                    }),
                    $nest: {
                        "&:active, &:hover, &:focus, &.focus-visible": {
                            borderColor: colorOut(vars.searchBar.state.border.color),
                        },
                    },
                    ...borderRadii({
                        left: vars.border.radius,
                        right: mirrorLeftRadius ? important(vars.border.radius) : important(0),
                    }),
                    borderColor: colorOut(vars.searchBar.border.color),
                },
                ".searchBar__control": {
                    cursor: "text",
                    position: "relative",
                },
                "& .searchBar__placeholder": {
                    color: colorOut(vars.searchBar.font.color),
                },
            },
        } as NestedCSSProperties);
    };

    const outerBackground = (url?: string) => {
        const finalUrl = url ?? vars.outerBackground.image ?? undefined;
        const finalVars = {
            ...vars.outerBackground,
            image: finalUrl,
        };

        return style(
            "outerBackground",
            {
                position: "absolute",
                top: 0,
                left: 0,
                width: percent(100),
                height: calc(`100% + 2px`),
                transform: translateY(`-1px`), // Depending on how the browser rounds the pixels, there is sometimes a 1px gap above the banner
                display: "block",
                ...backgroundHelper(finalVars),
            },
            mediaQueries.oneColumnDown({
                image: vars.outerBackground.mobile.image,
            } as NestedCSSProperties),
        );
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
        height: calc(`100% + 2px`),
        background: colorOut(vars.backgrounds.overlayColor),
    });

    const contentContainer = (hasFullWidth = false) => {
        return style(
            "contentContainer",
            {
                display: "flex",
                flexDirection: "column",
                justifyContent: "center",
                alignItems: vars.options.alignment === BannerAlignment.LEFT ? "flex-start" : "center",
                ...paddings(vars.contentContainer.padding),
                ...backgroundHelper(vars.innerBackground),
                minWidth: unit(vars.contentContainer.minWidth),
                minHeight: unit(vars.dimensions.minHeight),
                width: hasFullWidth || vars.options.alignment === BannerAlignment.LEFT ? percent(100) : undefined,
            },
            mediaQueries.oneColumnDown({
                minWidth: percent(100),
                maxWidth: percent(100),
                minHeight: unitIfDefined(vars.dimensions.mobile.minHeight),
                ...paddings(vars.contentContainer.mobile.padding),
            }),
        );
    };

    const text = style("text", {
        color: colorOut(vars.colors.primaryContrast),
    });

    const noTopMargin = style("noTopMargin", {});

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
                [`&.${noTopMargin}`]: {
                    marginTop: 0,
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...margins(vars.searchBar.marginMobile),
            [noTopMargin]: {
                marginTop: 0,
            },
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
        boxSizing: "border-box",
        zIndex: 1,
        boxShadow: vars.searchBar.shadow.show ? vars.searchBar.shadow.style : undefined,
        height: unit(vars.searchBar.sizing.height),
        $nest: {
            "&.hasFocus .searchBar-valueContainer": {
                boxShadow: `0 0 0 1px ${colorOut(vars.colors.primary)} inset`,
            },
            "& .searchBar-valueContainer icon-clear": {
                color: colorOut(vars.searchBar.font.color),
            },
            [`& .${searchBarClasses().icon}, & .searchBar__input`]: {
                color: colorOut(vars.searchBar.font.color),
            },
        },
    });

    const imagePositioner = style("imagePositioner", {
        display: "flex",
        flexDirection: "row",
        flexWrap: "nowrap",
        alignItems: "center",
        maxWidth: percent(100),
    });

    const makeImageMinWidth = (rootUnit, padding) => {
        const values = [
            rootUnit,
            negative(vars.contentContainer.minWidth),
            negative(vars.contentContainer.padding.horizontal),
            negative(padding),
        ];

        const stringValues = [];
        let simplifiedNumber = 0;

        values.forEach(value => {
            if (typeof value === "number") {
                simplifiedNumber += value;
            } else {
                if (value) {
                    stringValues.push(unit(value) as never);
                }
            }
        });

        // @ts-ignore
        let simplifiedNumberOutput = simplifiedNumber ? unit(simplifiedNumber.toString()).toString() : "";

        if (simplifiedNumberOutput.startsWith("-")) {
            simplifiedNumberOutput = simplifiedNumberOutput.replace("-", "- ");
        }

        if (stringValues.length > 0) {
            return calc(`${stringValues.join(" + ")} + ${unit(simplifiedNumberOutput)}`.replace("+ -", "-").trim());
        } else {
            return unit(simplifiedNumberOutput);
        }
    };

    const imageElementContainer = style(
        "imageElementContainer",
        {
            alignSelf: "stretch",
            width: makeImageMinWidth(globalVars.content.width, containerVariables().spacing.padding.horizontal),
            flexGrow: 1,
            position: "relative",
            overflow: "hidden",
            ...paddings(vars.rightImage.padding),
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
                width: makeImageMinWidth("100vw", containerVariables().spacing.mobile.padding.horizontal),
            }),
        media(
            { maxWidth: 500 },
            {
                display: "none",
            },
        ),
    );

    const logoContainer = style("logoContainer", {
        display: "flex",
        width: percent(100),
        height: unit(vars.logo.height),
        maxWidth: percent(100),
        minHeight: unit(vars.logo.height),
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        overflow: "hidden",
    });

    const logoSpacer = style("logoSpacer", {
        ...paddings(vars.logo.padding),
    });

    const logo = style("logo", {
        height: unit(vars.logo.height),
        width: unit(vars.logo.width),
        maxHeight: percent(100),
        maxWidth: percent(100),
    });

    const rightImage = style(
        "rightImage",
        {
            ...absolutePosition.middleRightOfParent(),
            minWidth: unit(vars.rightImage.minWidth),
            objectPosition: "100% 50%",
            objectFit: "contain",
            marginLeft: "auto",
            right: 0,
        },
        media(
            {
                maxWidth: calc(
                    `${unit(vars.rightImage.minWidth)} + ${unit(vars.contentContainer.minWidth)} + ${unit(
                        vars.rightImage.padding.horizontal ?? vars.rightImage.padding.all,
                    )} * 2`,
                ),
            },
            { right: "initial", objectPosition: "0% 50%" },
        ),
    );

    const rootConditionalStyles =
        presets.input.preset === SearchBarPresets.UNIFIED_BORDER
            ? {
                  backgroundColor: colorOut(vars.unifiedBannerOptions.border.color),
                  boxShadow: `0 0 0 ${unit(vars.unifiedBannerOptions.border.width)} ${
                      vars.unifiedBannerOptions.border.color
                  }`,
              }
            : {};

    const root = style({
        position: "relative",
        maxWidth: percent(100),
        backgroundColor: colorOut(vars.outerBackground.color),
        $nest: {
            [`& .${searchBarClasses().independentRoot}`]: rootConditionalStyles,
            "& .searchBar": {
                height: unit(vars.searchBar.sizing.height),
            },
        },
    });

    const iconContainer = style("iconContainer", {
        $nest: {
            "&&": {
                height: unit(vars.searchBar.sizing.height),
                outline: 0,
                border: 0,
                background: "transparent",
            },
        },
    });

    const resultsAsModal = style("resultsAsModalClasses", {
        $nest: {
            "&&": {
                top: unit(vars.searchBar.sizing.height),
            },
        },
    });

    const middleContainer = style(
        "middleContainer",
        {
            position: "relative",
            minHeight: unit(vars.dimensions.minHeight),
        },
        mediaQueries.oneColumnDown({
            minHeight: unitIfDefined(vars.dimensions.mobile.minHeight),
        }),
    );

    const searchStrip = style(
        "searchStrip",
        {
            position: "relative",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 1,
            background: colorOut(vars.searchStrip.bg),
            ...paddings(vars.searchStrip.padding),
            minHeight: unitIfDefined(vars.searchStrip.minHeight),
            marginTop: unitIfDefined(vars.searchStrip.offset),
        },
        mediaQueries.oneColumnDown({
            background: vars.searchStrip.mobile.bg ? colorOut(vars.searchStrip.mobile.bg) : undefined,
            ...paddings(vars.searchStrip.mobile.padding),
            minHeight: unitIfDefined(vars.searchStrip.mobile.minHeight),
            marginTop: unitIfDefined(vars.searchStrip.mobile.offset),
        }),
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
        iconContainer,
        resultsAsModal,
        backgroundOverlay,
        imageElementContainer,
        rightImage,
        middleContainer,
        imagePositioner,
        searchStrip,
        noTopMargin,
        logoContainer,
        logoSpacer,
        logo,
    };
});
