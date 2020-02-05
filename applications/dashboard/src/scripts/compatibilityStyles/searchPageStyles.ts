/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpersColors";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { margins, unit } from "@library/styles/styleHelpers";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { metaContainerStyles } from "@vanilla/library/src/scripts/styles/metasStyles";

export const searchPageCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();

    cssOut(`.DataList.DataList-Search .Item.Item-Search .Img.PhotoWrap`, {
        top: unit(layoutVars.cell.paddings.vertical),
        left: unit(layoutVars.cell.paddings.horizontal),
    });

    cssOut(
        `
        .DataList a,
        .DataList-Search a,
        .Breadcrumbs a,
        .MessageList a,
        .DataList-Search .MItem-Author a,
        .DataTableWrap a,
        .Container .Frame-contentWrap .ChildCategories a,
        .DataList.DataList-Search .Item.Item-Search h3 a
        `,
        {
            textDecoration: "none",
            color: colorOut(globalVars.mainColors.fg),
        },
    );

    cssOut(`.Item.Item-Search .Meta .Bullet`, {
        display: "none",
    });

    cssOut(`#Form_within`, {
        marginBottom: unit(globalVars.gutter.size),
    });

    cssOut(`.DataList.DataList-Search .Item.Item-Search .Media-Body .Meta`, {
        ...metaContainerStyles(),
        $nest: {
            "& .Bullet": {
                display: "none",
            },
            // "& > *": {},
        },
    });
    cssOut(`.DataList.DataList-Search .Item.Item-Search .Media-Body .Summary`, {
        $nest: {
            "& .Bullet": {
                display: "none",
            },
        },
    });

    cssOut(`.DataList.DataList-Search .Breadcrumbs`, {
        overflow: "visible",
    });

    cssOut(`.DataList.DataList-Search .Item-Body.Media`, {
        margin: 0,
    });

    cssOut(`.DataList.DataList-Search + .PageControls.Bottom`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",

        $nest: {
            "& .Gloss": {
                margin: 0,
                minHeight: 0,
                minWidth: 0,
            },
            "& .Pager": {
                float: "none",
                marginRight: "auto",
            },
        },
    });

    cssOut(`.DataList.DataList-Search .Crumb`, {
        ...margins({
            right: -6,
            left: -6,
        }),
    });

    cssOut(`#Panel .FilterMenu .Aside, .PanelInfo .Aside, .Item .Aside`, {
        float: "none",
        display: "block",
        margin: `0 0 14px`,
    });
};
