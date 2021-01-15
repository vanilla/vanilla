import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const tagVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumFonts");
    const globalVars = globalVariables();

    const colorOverwrite = makeThemeVars("colorOverwrite", {
        ...Variables.clickable({}),
        skipDefault: undefined,
    });

    const linkColors = Mixins.clickable.itemState(colorOverwrite, { disableTextDecoration: true });

    const nested = makeThemeVars("states", linkColors);

    const colors = makeThemeVars("color", {
        fg: globalVars.meta.text.color,
    });

    const font = makeThemeVars(
        "font",
        Variables.font({
            color: colors.fg,
            lineHeight: globalVars.lineHeights.meta,
            size: globalVars.fonts.size.small,
        }),
    );

    const padding = makeThemeVars(
        "padding",
        Variables.spacing({
            vertical: 2,
            horizontal: 9,
        }),
    );

    const margin = makeThemeVars(
        "margin",
        Variables.spacing({
            vertical: 2,
            horizontal: globalVars.meta.spacing.default,
        }),
    );

    const border = makeThemeVars(
        "border",
        Variables.border({
            color: colors.fg,
            width: 1, // these are really small, I don't think it makes sense to default to anything else.
        }),
    );

    // If border radius not overwritten, calculate it to be round.
    if (!border.radius) {
        border.radius =
            ((((font.lineHeight || 1.45) as number) * ((font.size as number) ?? 12)) as number) / 2 +
            (padding.vertical as number) +
            (!!border.width && border.width > 0 ? (border.width as number) : 0);
    }

    const output = {
        colors,
        font,
        padding,
        border,
        margin,

        nested,
    };

    return output;
});

// For now we only have compatibility styles in //forumTagStyles.ts
