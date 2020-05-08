/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { EMPTY_FONTS, IFont } from "@library/styles/styleHelpersTypography";
import { paddings } from "@library/styles/styleHelpersSpacing";
import { TextAlignLastProperty, TextAlignProperty } from "csstype";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const dataListVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("dataList", forcedVars);
    const globalVars = globalVariables();

    const spacing = makeThemeVars("spacing", {
        padding: {
            vertical: 6,
        },
    });

    const key = makeThemeVars("key", {
        textAlignment: "left" as TextAlignProperty,
        padding: {
            right: globalVars.spacer.size,
        },
        font: {
            ...EMPTY_FONTS,
            size: globalVars.fonts.size.small,
            weight: globalVars.fonts.weights.bold,
            lineHeight: globalVars.lineHeights.condensed,
        } as IFont,
    });

    const value = makeThemeVars("value", {
        textAlignment: "left" as TextAlignProperty,
    });

    return {
        spacing,
        key,
        value,
    };
});

export const dataListClasses = useThemeCache((layoutMediaQueries?: { xs: any }) => {
    const style = styleFactory("dataList");
    const vars = dataListVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutMediaQueries ?? layoutVariables().mediaQueries();

    const root = style({});

    const table = style(
        "table",
        {},
        mediaQueries.xs({
            display: "block",
        }),
    );

    const row = style(
        "row",
        {},
        mediaQueries.xs({
            display: "block",
        }),
    );

    const key = style(
        "key",
        {
            textAlign: vars.key.textAlignment,
            ...paddings({
                ...vars.spacing.padding,
                ...vars.key.padding,
            }),
        },
        mediaQueries.xs({
            display: "block",
            ...paddings({
                horizontal: 0,
                bottom: 0,
            }),
        }),
    );

    const value = style(
        "value",
        {
            textAlign: vars.key.textAlignment,
        },
        mediaQueries.xs({
            display: "block",
            ...paddings({
                horizontal: 0,
                top: 2,
            }),
        }),
    );

    return {
        root,
        table,
        row,
        key,
        value,
    };
});
