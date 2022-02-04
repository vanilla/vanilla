/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Property } from "csstype";
import { panelLayoutVariables } from "@library/layout/PanelLayout.variables";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const dataListVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("dataList", forcedVars);
    const globalVars = globalVariables();

    const spacing = makeThemeVars("spacing", {
        padding: {
            vertical: 6,
        },
    });

    const key = makeThemeVars("key", {
        textAlignment: "left" as Property.TextAlign,
        padding: {
            vertical: spacing.padding.vertical,
            right: globalVars.spacer.size,
        },
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("small", "bold"),
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    const value = makeThemeVars("value", {
        textAlignment: "left" as "left" | "center",
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
    const mediaQueries = layoutMediaQueries ?? panelLayoutVariables().mediaQueries();

    const root = style({});

    const table = style(
        "table",
        {},
        mediaQueries.xs({
            ...{
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
        fontWeight: globalVars.fonts.weights.bold,
        ...Mixins.padding(vars.key.padding),
    });

    const value = style("value", {
        textAlign: vars.key.textAlignment,
        verticalAlign: "top",
        ...Mixins.padding(vars.value.padding),
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
