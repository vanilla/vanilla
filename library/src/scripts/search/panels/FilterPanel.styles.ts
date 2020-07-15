/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { fonts, paddings, srOnly, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export const filterPanelClasses = useThemeCache(mediaQueries => {
    const globalVars = globalVariables();
    const style = styleFactory("filterPanel");

    const header = style(
        "header",
        {
            marginBottom: unit(globalVars.gutter.size * 1.5),
            $nest: {
                "&&": {
                    border: 0,
                    ...paddings({
                        horizontal: 0,
                        bottom: 0,
                    }),
                },
            },
        },
        mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    ...srOnly(),
                },
            },
        }),
    );

    const body = style("body", {
        $nest: {
            "&&": {
                ...paddings({
                    horizontal: 0,
                }),
            },
        },
    });

    const footer = style("body", {
        $nest: {
            "&&": {
                border: 0,
                marginTop: unit(globalVars.gutter.size),
                ...paddings({
                    horizontal: 0,
                }),
            },
        },
    });

    const title = style("title", {
        $nest: {
            "&&": {
                ...fonts({
                    size: globalVars.fonts.size.large,
                    weight: globalVars.fonts.weights.bold,
                }),
            },
        },
    });

    return {
        header,
        body,
        footer,
        title,
    };
});
