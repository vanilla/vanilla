/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { important } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const forumCategoriesVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumCategories");
    const image = makeThemeVars("image", {
        width: 40,
    });

    return { image };
});

export const categoriesCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const vars = forumCategoriesVariables();

    // Category list

    cssOut(`.DataList .Item .Title`, {
        marginBottom: unit(4),
    });

    cssOut(`.ItemContent.Category`, {
        position: "relative",
    });

    cssOut(`.DataList .PhotoWrap, .MessageList .PhotoWrap`, {
        top: unit(2),
    });

    cssOut(`.categoryList-heading`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`.CategoryGroup`, {
        marginTop: unit(globalVars.gutter.size * 2.5),
    });

    cssOut(`.Groups .DataTable.CategoryTable thead .CategoryName, .DataTable.CategoryTable thead .CategoryName`, {
        paddingLeft: unit(layoutVars.cell.paddings.horizontal),
    });

    cssOut(
        `.Groups .DataTable h2, .Groups .DataTable h3, .Groups .DataTable .Title.Title, .DataTable h2, .DataTable h3, .DataTable .Title.Title`,
        {
            marginBottom: 0,
        },
    );

    cssOut(`.CategoryNameHeading.isEmptyDescription + .CategoryDescription`, {
        display: important("none"),
    });

    cssOut(`.CategoryNameHeading.isEmptyDescription`, {
        minHeight: unit(vars.image.width),
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        alignItems: "flex-start",
    });

    cssOut(
        `.Groups .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap, .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap`,
        {
            width: unit(vars.image.width),
            height: unit(vars.image.width),
        },
    );
};
