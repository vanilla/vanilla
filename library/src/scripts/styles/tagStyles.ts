import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, EMPTY_BORDER, IBorderStyles } from "@library/styles/styleHelpersBorders";
import { EMPTY_SPACING } from "@library/styles/styleHelpersSpacing";
import { EMPTY_FONTS, IFont } from "@library/styles/styleHelpersTypography";
import {
    ILinkColorOverwritesWithOptions,
    EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS,
} from "@library/styles/styleHelpersLinks";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const tagVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumFonts");
    const globalVars = globalVariables();

    const colorOverwrite = makeThemeVars("colorOverwrite", {
        ...(EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS as ILinkColorOverwritesWithOptions),
    } as ILinkColorOverwritesWithOptions);

    const linkColors = clickableItemStates(colorOverwrite, { disableTextDecoration: true });

    const $nest = makeThemeVars("states", linkColors.$nest || {});

    const colors = makeThemeVars("color", {
        fg: globalVars.elementaryColors.lowContrast,
    });

    const font = makeThemeVars("font", {
        ...EMPTY_FONTS,
        color: colors.fg,
        lineHeight: globalVars.lineHeights.meta,
        size: globalVars.fonts.size.small,
    } as IFont);

    const padding = makeThemeVars("padding", {
        ...EMPTY_SPACING,
        vertical: 2,
        horizontal: 9,
    });

    const margin = makeThemeVars("margin", {
        ...EMPTY_SPACING,
        vertical: 2,
        horizontal: globalVars.meta.spacing.default,
    });

    const border = makeThemeVars("border", {
        ...EMPTY_BORDER,
        fg: colors.fg,
        width: 1, // these are really small, I don't think it makes sense to default to anything else.
    } as IBorderStyles);

    // If border radius not overwritten, calculate it to be round.
    if (!border.radius) {
        border.radius =
            ((((font.lineHeight || 1.45) as number) * ((font.size as number) ?? 12)) as number) / 2 +
            padding.vertical +
            (!!border.width && border.width > 0 ? (border.width as number) : 0);
    }

    const output = {
        colors,
        font,
        padding,
        border,
        margin,
        $nest,
    };

    return output;
});

// For now we only have compatibility styles in //forumTagStyles.ts
