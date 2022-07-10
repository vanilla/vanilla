/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { calc, important, percent, translateY } from "csx";
import { Mixins } from "@library/styles/Mixins";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";

export const categoriesCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();

    // Category list

    cssOut(`.DataList .Item .Title`, {
        marginBottom: styleUnit(4),
    });

    cssOut(`.ItemContent.Category`, {
        position: "relative",
    });

    cssOut(`.categoryList-heading`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`.CategoryGroup`, {
        marginTop: styleUnit(globalVars.gutter.size * 2.5),
    });

    cssOut(`.Groups .DataTable.CategoryTable thead .CategoryName, .DataTable.CategoryTable thead .CategoryName`, {
        paddingLeft: styleUnit(layoutVars.cell.paddings.horizontal),
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

    const photoVars = userPhotoVariables();

    cssOut(
        `.Groups .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap, .DataTable.CategoryTable tbody td.CategoryName .PhotoWrap`,
        {
            width: styleUnit(photoVars.sizing.medium),
            height: styleUnit(photoVars.sizing.medium),
        },
    );

    cssOut(`.CategoryBox`, {
        position: "relative",
    });

    cssOut(`.CategoryBox .H`, {
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("largeTitle"),
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
        width: calc(`100% - ${styleUnit(35)}`),
    });

    cssOut(`.CategoryBox-Head .OptionsMenu`, {
        float: "none",
        transform: translateY(`-50%`),
        ...Mixins.margin({
            horizontal: 0,
            top: styleUnit((globalVars.fonts.size.largeTitle * globalVars.lineHeights.condensed) / 2),
            left: "auto",
        }),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories .Heading`, {
        paddingLeft: 0,
        paddingTop: styleUnit(18),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large", "bold"),
        }),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories .Heading .Count`, {
        display: "none",
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories  Li.Depth1`, {
        paddingTop: styleUnit(18),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories  Li.Depth1 a.ItemLink`, {
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
        }),
    });

    cssOut(`.Panel .Box.BoxCategories .PanelInfo.PanelCategories li.Depth2 a.ItemLink`, {
        fontWeight: globalVars.fonts.weights.normal,
    });

    cssOut(`.DataList .Item, .DataList .Empty`, {
        width: percent(100),
    });
};
