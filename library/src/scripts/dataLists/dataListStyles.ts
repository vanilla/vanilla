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
            vertical: spacing.padding.vertical,
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
        padding: {
            vertical: spacing.padding.vertical,
        },
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
            $nest: {
                "&&": {
                    display: "block",
                },
            },
        }),
    );

    const key = style("key", {
        textAlign: vars.key.textAlignment,
        verticalAlign: "top",
        whiteSpace: "nowrap",
        ...paddings(vars.key.padding),
    });

    const value = style("value", {
        textAlign: vars.key.textAlignment,
        verticalAlign: "top",
        ...paddings(vars.value.padding),
    });

    const row = style("row", {});

    return {
        root,
        table,
        row,
        key,
        value,
    };
});
