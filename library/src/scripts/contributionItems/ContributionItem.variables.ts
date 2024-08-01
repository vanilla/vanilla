/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory, useThemeCache, VariableMapping } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";

// Used for badges and reactions. See contributionItemsExpander for documentation.
export const contributionItemVariables = useThemeCache(
    (componentName: string, mapping?: VariableMapping | VariableMapping[]) => {
        const global = globalVariables();

        const makeThemeVars = variableFactory(componentName, undefined, mapping);

        const sizing = makeThemeVars("sizing", {
            width: 38,
        });

        const spacing = makeThemeVars(
            "spacing",
            Variables.spacing({
                horizontal: 18,
                vertical: 22,
            }),
        );

        const count = makeThemeVars("count", {
            display: true,
            height: 17,
            size: global.fonts.size.small,
            backgroundColor: ColorsUtils.colorOut("#808080"),
            borderColor: global.elementaryColors.black,
        });

        const name = makeThemeVars("name", {
            display: false,
            width: 80,
            spacing: Variables.spacing({
                left: 10,
            }),
            font: Variables.font({
                ...global.fontSizeAndWeightVars("small", "semiBold"),
                lineHeight: 16 / 12,
                color: ColorsUtils.colorOut(global.mainColors.fg),
            }),
        });

        const limit = makeThemeVars("limit", {
            maxItems: 20,
        });

        const stackedListVars = stackedListVariables(`${componentName}StackedList`);
        stackedListVars.sizing.width = sizing.width;
        stackedListVars.sizing.offset = 8;
        stackedListVars.plus.margin = 12;

        const stackedList = makeThemeVars("stackedList", stackedListVars);

        return {
            sizing,
            spacing,
            count,
            limit,
            name,
            stackedList,
        };
    },
);
