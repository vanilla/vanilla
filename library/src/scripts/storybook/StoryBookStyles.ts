/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { margins } from "@library/styles/styleHelpers";
import { important } from "csx";

export const storyBookClasses = useThemeCache(() => {
    const style = styleFactory("storyBookStyles");

    const heading = style("heading", {

    });
    const paragraph = style("paragraph", {

    });

    const unorderedList = style("unorderedList", {

    });

    const listItem = style("listItem", {

    });
    const separator = style("separator", {

    });
    const link = style("link", {

    });

    return {
        heading,
        paragraph,
        unorderedList, listItem, separator,
        link
    };
});
