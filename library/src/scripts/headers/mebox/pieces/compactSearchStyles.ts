/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, rgba } from "csx";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { IThemeVariables } from "@library/theming/themeReducer";
import { inputClasses } from "@library/forms/inputStyles";
import { css } from "@emotion/css";

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

    let baseColor = ColorsUtils.modifyColorBasedOnLightness({ color: titleBarVars.colors.bg, weight: 0.2 });
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
            ? globalVars.elementaryColors.black.fade(0.25)
            : globalVars.elementaryColors.white.fade(0.25),
    });

    const bgColor = isTransparentButton ? rgba(0, 0, 0, 0) : colors.primary;
    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const fgColor = isTransparentButton ? colors.contrast : colors.fg;
    const activeBorderColor = isTransparentButton ? colors.contrast : colors.bg;

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

    return { colors, searchBar, backgrounds };
});

export const compactSearchClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();
    const vars = compactSearchVariables();
    inputClasses().applyInputCSSRules();

    const root = css({
        ...{
            ".searchBar": {
                flexGrow: 1,
            },
            ".searchBar__input": {
                color: ColorsUtils.colorOut(vars.searchBar.font.color),
                width: percent(100),
            },
            ".searchBar__input input": {
                color: ColorsUtils.colorOut(vars.searchBar.font.color),
                borderRadius: important(0),
            },
            ".searchBar-valueContainer": {
                height: styleUnit(formElementsVars.sizing.height),
            },
            ".searchBar__placeholder": {
                color: ColorsUtils.colorOut(vars.colors.placeholder),
            },
            ".searchBar-icon": {
                color: ColorsUtils.colorOut(vars.colors.placeholder),
            },
            "&.isOpen": {
                maxWidth: percent(100),
            },
            "&.isCentered": {
                margin: "auto",
            },
            ".suggestedTextInput-inputTextutText": {
                borderTopRightRadius: styleUnit(globalVars.border.radius),
                borderBottomRightRadius: styleUnit(globalVars.border.radius),
            },
        },
    });

    const contents = css({
        display: "flex",
        alignItems: "center",
        flexWrap: "nowrap",
        minHeight: styleUnit(formElementsVars.sizing.height),
        justifyContent: "center",
        width: percent(100),
        position: "relative",
    });

    const close = css({
        color: "inherit",
        whiteSpace: "nowrap",
        fontWeight: globalVars.fonts.weights.semiBold,
        margin: 0,
        outline: 0,
        ...Mixins.border({
            radius: 0,
            color: globalVars.elementaryColors.transparent,
        }),
        ...Mixins.padding({
            horizontal: 10,
        }),
    });

    const cancelContents = css({});

    const searchAndResults = css({
        flex: 1,
        position: "relative",
        width: percent(100),
        height: styleUnit(formElementsVars.sizing.height),
        display: "flex",
        flexWrap: "nowrap",
        ...Mixins.margin({
            horizontal: 1,
        }),
    });

    const valueContainer = css({});

    const compactSearchWrapper = css({
        display: "flex",
        alignItems: "center",
        flexDirection: "column",
    });

    return {
        root,
        contents,
        close,
        cancelContents,
        searchAndResults,
        valueContainer,
        compactSearchWrapper,
    };
});
