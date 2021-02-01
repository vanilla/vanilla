/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IStateColors } from "@library/styles/styleHelpers";
import { IThemeVariables } from "@library/theming/themeReducer";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";

export const searchBarVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("searchBar", forcedVars);

    const search = makeThemeVars("search", {
        minWidth: 109,
        fullBorderRadius: {
            extraHorizontalPadding: 10,
        },
        compact: {
            minWidth: formElementVars.sizing.height,
        },
    });

    const placeholder = makeThemeVars("placeholder", {
        color: formElementVars.placeholder.color,
    });

    const heading = makeThemeVars("heading", {
        margin: 12,
    });

    const border = makeThemeVars("border", {
        color: globalVars.border.color,
        width: globalVars.borderType.formElements.default.width,
        radius: globalVars.borderType.formElements.default.radius,
        inset: false,
    });

    const sizingInit = makeThemeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const sizing = makeThemeVars("sizing", {
        height: sizingInit.height,
        heightMinusBorder: sizingInit.height - border.width * 2,
    });

    const input = makeThemeVars("input", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const searchIcon = makeThemeVars("searchIcon", {
        gap: 40,
        height: 13,
        width: 14,
        fg: input.fg.fade(0.7),
        padding: {
            right: 5,
        },
    });

    const results = makeThemeVars("results", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        borderRadius: globalVars.border.radius,
    });

    const scope = makeThemeVars("scope", {
        width: 142,
        padding: 18,
        compact: {
            padding: 12,
            width: 58,
        },
    });

    const scopeIcon = makeThemeVars("scopeIcon", {
        width: 10,
        ratio: 6 / 10,
    });

    const stateColorsInit = makeThemeVars("stateColors", {
        allStates: globalVars.mainColors.primary,
        hoverOpacity: globalVars.constants.states.hover.borderEmphasis,
    });

    const stateColors: IStateColors = makeThemeVars("stateColors", {
        ...stateColorsInit,
        hover: stateColorsInit.allStates.fade(stateColorsInit.hoverOpacity),
        focus: stateColorsInit.allStates,
        active: stateColorsInit.allStates,
    });

    // Used when `SearchBarPresets.NO_BORDER` is active
    const noBorder = makeThemeVars("noBorder", {
        offset: 1,
    });

    // Used when `SearchBarPresets.BORDER` is active
    const withBorder = makeThemeVars("withBorder", {
        borderColor: globalVars.border.color,
    });

    const options = makeThemeVars("options", {
        compact: false,
        preset: SearchBarPresets.NO_BORDER,
    });

    return {
        search,
        noBorder,
        withBorder,
        searchIcon,
        sizing,
        placeholder,
        input,
        heading,
        results,
        border,
        scope,
        scopeIcon,
        stateColors,
        options,
    };
});
