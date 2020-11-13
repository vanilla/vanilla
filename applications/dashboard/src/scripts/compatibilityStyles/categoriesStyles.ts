/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, fonts, importantUnit, margins, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { calc, important, percent, translateY } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { forumVariables } from "@library/forms/forumStyleVars";

export const forumCategoriesVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumCategories");
    const image = makeThemeVars("image", {
        width: forumVariables().userPhoto.sizing.medium,
    });

    return {
        image: image,
    };
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

    cssOut(`a.Bookmark`, {
        backgroundImage: important("none"),
        color: important("transparent"),
        font: `0/0 a`,
        height: "auto",
        textIndent: "0",
        width: "auto",
        verticalAlign: "top",
        overflow: "hidden",
        fontSize: importantUnit(1),
        textDecoration: important("none"),
    });

    cssOut(
        `.Groups .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap, .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap`,
        {
            width: unit(vars.image.width),
            height: unit(vars.image.width),
        },
    );

    cssOut(`.CategoryBox`, {
        position: "relative",
    });

    cssOut(`.CategoryBox .H`, {
        ...fonts({
            size: globalVars.fonts.size.largeTitle,
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    cssOut(`.CategoryBox-Head`, {
        position: "relative",
        display: "flex",
        flexWrap: "wrap",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        width: percent(100),
    });

    cssOut(`.CategoryBox-Head .H`, {
        width: calc(`100% - ${unit(35)}`),
    });

    cssOut(`.CategoryBox-Head .OptionsMenu`, {
        float: "none",
        transform: translateY(`-50%`),
        ...margins({
            horizontal: 0,
            top: unit((globalVars.fonts.size.largeTitle * globalVars.lineHeights.condensed) / 2),
            left: "auto",
        }),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories .Heading`, {
        paddingLeft: 0,
        paddingTop: unit(18),
        fontWeight: globalVars.fonts.weights.bold,
        fontSize: unit(globalVars.fonts.size.large),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories .Heading .Count`, {
        display: "none",
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories  Li.Depth1`, {
        paddingTop: unit(18),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories  Li.Depth1 a.ItemLink`, {
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: unit(globalVars.fonts.size.large),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories li.Depth2 a.ItemLink`, {
        fontWeight: globalVars.fonts.weights.normal,
    });

    cssOut(`.DataList .Item, .DataList .Empty`, {
        width: percent(100),
    });
};
