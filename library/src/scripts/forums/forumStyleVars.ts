/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorHelper } from "csx";
import { TLength } from "@library/styles/styleShim";
import { MarginProperty, MarginTopProperty } from "csstype";

export const forumVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("forum", forcedVars);

    const modern = makeThemeVars("modern", {});
    const table = makeThemeVars("table", {});

    const lists = makeThemeVars("lists", {
        spacing: {
            padding: {
                top: 15,
                right: globalVars.gutter.half,
                bottom: 16,
                left: globalVars.gutter.half,
            },
            margin: {
                top: "initial" as MarginTopProperty<TLength>,
                right: "initial",
                bottom: "initial",
                left: "initial",
            },
        },
        colors: {
            margin: undefined as MarginProperty<TLength> | undefined,
            bg: undefined as ColorHelper | undefined,
            read: {
                bg: globalVars.mainColors.bg,
            },
        },
    });

    const discussions = makeThemeVars("discussions", {
        modern: {
            ...modern,
            lists,
        },
        table: {
            ...table,
        },
    });

    const discussion = makeThemeVars("discussion", {});
    const categories = makeThemeVars("categories", {
        modern: {
            ...modern,
        },
        table: {
            ...table,
        },
        mixed: {},
    });

    return {
        modern,
        table,
        lists,
        discussions,
        discussion,
        categories,
    };
});
