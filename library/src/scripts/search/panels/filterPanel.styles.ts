/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

import { globalVariables } from "@library/styles/globalStyleVars";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export const filterPanelClasses = useThemeCache((mediaQueries) => {
    const globalVars = globalVariables();
    const style = styleFactory("filterPanel");

    const header = style(
        "header",
        {
            marginBottom: styleUnit(globalVars.gutter.size * 1.5),
            ...{
                "&&": {
                    border: 0,
                    ...Mixins.padding({
                        horizontal: 0,
                        bottom: 0,
                    }),
                },
            },
        },
        mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    ...Mixins.absolute.srOnly(),
                },
            },
        }),
    );

    const body = style("body", {
        ...{
            "&&": {
                ...Mixins.padding({
                    horizontal: 0,
                }),
            },
        },
    });

    const footer = style("body", {
        ...{
            "&&": {
                border: 0,
                marginTop: styleUnit(globalVars.gutter.size),
                ...Mixins.padding({
                    horizontal: 0,
                }),
            },
        },
    });

    const title = style("title", {
        ...{
            "&&": {
                ...Mixins.font({
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
