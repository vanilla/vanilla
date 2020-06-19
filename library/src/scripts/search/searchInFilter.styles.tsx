/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";

interface IProps extends IRadioInputAsButtonClasses {
    separator: string;
}

export const searchInFilterClasses = useThemeCache(() => {
    const style = styleFactory("searchIn");
    const vars = globalVariables();

    const root = style({});
    const items = style("items", {});
    const item = style("item", {});
    const label = style("label", {});
    const input = style("input", {});
    const separator = style("separator", {});

    return {
        root,
        items,
        item,
        label,
        input,
        separator,
    } as IProps;
});
