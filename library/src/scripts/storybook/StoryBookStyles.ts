/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import {fonts, margins} from "@library/styles/styleHelpers";
import { important } from "csx";
import {globalVariables} from "@library/styles/globalStyleVars";

export const storyBookClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("storyBookStyles");

    const heading = style("heading", {
        display: "block",
        ...fonts({
            size: 24,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
            lineHeight: 1.25,
        }),
    });


    const headingH1 = style("headingH1", {

    });

    const headingH2 = style("headingH2", {

    });


    const paragraph = style("paragraph", {
        display: "block",
        ...fonts({
            size: 14,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
            lineHeight: 1.43
        }),
    });

    const unorderedList = style("unorderedList", {

    });

    const listItem = style("listItem", {

    });
    const separator = style("separator", {

    });
    const link = style("link", {

    });
    const sectionHeading = style("sectionHeading", {

    });
    const containerOuter = style("contanerOuter", {
        position: "relative",
        display: "block",
        maxWidth: "100%",
    });
    const containerInner = style("contanerInner", {
        position: "relative",
        display: "block",
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
        containerOuter,
        containerInner,
    };
});
