/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";

// Used for badges and reactions. See contributionItemsExpander for documentation.
export const contributionItemVariables = useThemeCache((varFactoryName: string) => {
    const global = globalVariables();

    const makeThemeVars = variableFactory(varFactoryName);

    const sizing = makeThemeVars("sizing", {
        width: 40,
    });

    const spacing = makeThemeVars(
        "spacing",
        Variables.spacing({
            horizontal: 28,
            vertical: 16,
        }),
    );

    const count = makeThemeVars("count", {
        size: global.fonts.size.small,
        backgroundColor: ColorsUtils.colorOut("#808080"),
        borderColor: global.elementaryColors.black,
    });

    const name = makeThemeVars("name", {
        display: false,
        font: Variables.font({
            color: ColorsUtils.colorOut(global.mainColors.fg),
        }),
        spacing: Variables.spacing({}),
    });

    const limit = makeThemeVars("limit", {
        maxItems: 20,
    });

    return {
        sizing,
        spacing,
        count,
        limit,
        name,
    };
});
