/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit, modifyColorBasedOnLightness, IButtonStates } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorHelper, important, percent, px, rgba } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { SearchBarPresets } from "@library/banner/bannerStyles";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { IThemeVariables } from "@library/theming/themeReducer";

export const compactSearchVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("compactSearch", forcedVars);
    const titleBarVars = titleBarVariables(forcedVars);
    const formElVars = formElementsVariables(forcedVars);

    const searchButtonOptions = makeThemeVars("searchButtonOptions", { preset: ButtonPreset.TRANSPARENT });
    const searchInputOptions = makeThemeVars("searchInputOptions", { preset: SearchBarPresets.NO_BORDER });

    const isUnifiedBorder = searchInputOptions.preset === SearchBarPresets.UNIFIED_BORDER;
    const isTransparentButton = searchButtonOptions.preset === ButtonPreset.TRANSPARENT;
    const isSolidButton = searchButtonOptions.preset === ButtonPreset.SOLID || isUnifiedBorder; // force solid button when using unified border

    let baseColor = modifyColorBasedOnLightness(titleBarVars.colors.bg, 0.2);
    if (titleBarVars.colors.bgImage !== null) {
        // If we have a BG image, make sure we have some opacity so it shines through.
        baseColor = baseColor.fade(0.3);
    }
    // Main colors
    const colors = makeThemeVars("colors", {
        primary: globalVars.mainColors.primary,
        secondary: globalVars.mainColors.secondary,
        contrast: globalVars.elementaryColors.white,
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        borderColor: globalVars.mainColors.fg.fade(0.4),
        placeholder: globalVars.mainColors.fg.fade(0.8),
        active: {
            bg: baseColor,
        },
    });

    const isContrastLight = colors.contrast.lightness() >= 0.5;
    const backgrounds = makeThemeVars("backgrounds", {
        useOverlay: false,
        overlayColor: isContrastLight
            ? globalVars.elementaryColors.black.fade(0.3)
            : globalVars.elementaryColors.white.fade(0.3),
    });

    const bgColor = isTransparentButton ? rgba(0, 0, 0, 0) : colors.primary;
    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const fgColor = isTransparentButton ? colors.contrast : colors.fg;
    const activeBorderColor = isTransparentButton ? colors.contrast : colors.bg;

    const inputAndButton = makeThemeVars("inputAndButton", {
        borderRadius: globalVars.border.radius,
    });

    const searchBar = makeThemeVars("searchBar", {
        sizing: {
            height: formElVars.giantInput.height,
            width: 705,
        },
        font: {
            color: colors.fg,
            size: formElVars.giantInput.fontSize,
        },
        border: {
            leftColor: isTransparentButton ? colors.contrast : colors.borderColor,
            width: globalVars.border.width,
        },
    });

    const searchButton: IButtonType = makeThemeVars("searchButton", {
        name: "heroSearchButton",
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
            radius: {
                left: important(0),
                right: important(unit(inputAndButton.borderRadius) as string),
            },
        },
        fonts: {
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

    return { colors, inputAndButton, searchBar, searchButton, backgrounds };
});

export const compactSearchClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();
    const titleBarVars = titleBarVariables();
    const vars = compactSearchVariables();
    const style = styleFactory("compactSearch");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        $nest: {
            ".searchBar": {
                flexGrow: 1,
            },
            "& .searchBar__input": {
                color: colorOut(vars.searchBar.font.color),
                width: percent(100),
            },
            "& .searchBar__input input": {
                color: colorOut(vars.searchBar.font.color),
                borderRadius: important(0),
            },
            ".searchBar-valueContainer": {
                height: unit(formElementsVars.sizing.height),
                border: 0,
            },
            ".searchBar__placeholder": {
                color: colorOut(vars.colors.placeholder),
            },
            ".searchBar-icon": {
                color: colorOut(vars.colors.placeholder),
            },
            "&.isOpen": {
                maxWidth: percent(100),
            },
            "&.isCentered": {
                margin: "auto",
            },
            "& .suggestedTextInput-inputText": {
                borderTopRightRadius: unit(globalVars.border.radius),
                borderBottomRightRadius: unit(globalVars.border.radius),
            },
        },
    });

    const contents = style(
        "contents",
        {
            display: "flex",
            alignItems: "center",
            flexWrap: "nowrap",
            minHeight: unit(formElementsVars.sizing.height),
            justifyContent: "center",
            width: percent(100),
            position: "relative",
        },
        mediaQueries.oneColumnDown({
            height: unit(titleBarVars.sizing.mobile.height),
        }),
    );

    const close = style("close", {
        color: "inherit",
        whiteSpace: "nowrap",
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const cancelContents = style("cancelContents", {
        padding: px(4),
    });

    const searchAndResults = style("searchAndResults", {
        flex: 1,
        position: "relative",
        width: percent(100),
        height: unit(formElementsVars.sizing.height),
        display: "flex",
        flexWrap: "nowrap",
    });

    return {
        root,
        contents,
        close,
        cancelContents,
        searchAndResults,
    };
});
