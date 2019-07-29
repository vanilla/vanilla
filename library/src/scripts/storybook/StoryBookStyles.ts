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


    const headingH1 = style("headingH1", {

    });

    const headingH2 = style("headingH2", {

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
    const sectionHeading = style("link", {

    });

    return {
        heading,
        headingH1,
        headingH2,
        paragraph,
        unorderedList,
        listItem,
        separator,
        link,
        sectionHeading,
    };
});
