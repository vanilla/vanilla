/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { allLinkStates, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/searchBarStyles";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { important, percent } from "csx";
import { metaContainerStyles, metaItemStyle } from "@vanilla/library/src/scripts/styles/metasStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const searchPageCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const formElementVars = formElementsVariables();

    cssOut(`.DataList.DataList-Search .Item.Item-Search .Img.PhotoWrap`, {
        top: styleUnit(layoutVars.cell.paddings.vertical),
        left: styleUnit(layoutVars.cell.paddings.horizontal),
    });

    cssOut(
        `

         #search-results .MessageList a,
         #search-results .DataTableWrap a,
         #search-results .Container .Frame-contentWrap .ChildCategories a,
        .DataList#search-results a,
        .DataList-Search#search-results .MItem-Author,
        .DataList-Search#search-results .MItem-Author a,
        .DataList-Search#search-results a,
        .DataList-Search .MItem-Author a
        `,
        {
            textDecoration: "none",
            color: ColorsUtils.colorOut(globalVars.meta.text.color),
            fontSize: styleUnit(globalVars.meta.text.size),
        },
    );

    cssOut(
        `
          .DataList.DataList-Search#search-results .Item.Item-Search h3 a,
      `,
        {
            textDecoration: "none",
            ...Mixins.font({
                color: globalVars.mainColors.fg,
                size: globalVars.fonts.size.large,
                weight: globalVars.fonts.weights.semiBold,
                lineHeight: globalVars.lineHeights.condensed,
            }),
            ...allLinkStates({
                hover: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.hover),
                },
                keyboardFocus: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
                },
                focus: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.focus),
                },
                active: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.active),
                },
                visited: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.visited),
                },
            }),
        },
    );

    cssOut(`.Item.Item-Search .Meta .Bullet`, {
        display: important("none"),
    });

    cssOut(`#search-results.DataList.DataList-Search .Item.Item-Search .Media-Body .Meta`, {
        ...metaContainerStyles(),
    });

    cssOut(`#search-results.DataList.DataList-Search .Item.Item-Search .Media-Body .Bullet`, {
        display: "none",
    });

    cssOut(`#search-results .DataList.DataList-Search .Breadcrumbs`, {
        overflow: "visible",
    });

    cssOut(`#search-results .DataList.DataList-Search .Item-Body.Media`, {
        margin: 0,
    });

    cssOut(`#search-results .DataList.DataList-Search + .PageControls.Bottom`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        ...{
            ".Gloss": {
                margin: 0,
                minHeight: 0,
                minWidth: 0,
            },
            ".Pager": {
                float: "none",
                marginRight: "auto",
            },
        },
    });

    cssOut(`#search-results .DataList.DataList-Search .Crumb`, {
        ...Mixins.margin({
            right: -6,
            left: -6,
        }),
    });

    cssOut(`#search-results.DataList .Item-Body`, {
        paddingLeft: 0,
        margin: 0,
    });

    cssOut(`#search-results.DataList .Item h3`, {
        padding: 0,
        margin: 0,
    });

    cssOut(
        `
        #search-results .Media-Body .Meta .MItem-Location,
        `,
        {
            display: "inline-block",
            padding: 0,
            textTransform: "none",
        },
    );

    cssOut(`#search-results .Media-Body .Meta .Breadcrumbs`, {
        display: "inline",
        padding: 0,
        textTransform: "none",
    });

    cssOut(`#search-results .Item-Body .Meta`, metaContainerStyles());
    cssOut(`#search-results .Item-Body .Meta > *`, metaItemStyle());

    cssOut(`#search-results .Meta-Body.Meta .Breadcrumbs a`, {
        fontSize: styleUnit(globalVars.meta.text.size),
        textTransform: "initial",
    });

    cssOut(`#search-results .Meta-Body Meta`, {});

    cssOut(`#search-results .Item-Body.Media .PhotoWrap`, {
        display: "none",
    });

    // Search result styles
    const searchResultsStyles = searchBarClasses({
        preset: SearchBarPresets.BORDER,
    } as ISearchBarOverwrites).searchResultsStyles;
    const searchResultsVars = searchResultsVariables();

    cssOut(`body.Section-SearchResults .MenuItems.MenuItems-Input.ui-autocomplete`, {
        position: "relative",
        ...Mixins.padding({
            vertical: 0,
        }),
    });

    // li
    cssOut(`body.Section-SearchResults .MenuItems.MenuItems-Input.ui-autocomplete .ui-menu-item`, {
        position: "relative",
        padding: 0,
        margin: 0,
        ...{
            "& + .ui-menu-item": {
                borderTop: singleBorder({
                    color: searchResultsVars.separator.fg,
                    width: searchResultsVars.separator.width,
                }),
            },
        },
    });

    // a
    cssOut(`body.Section-SearchResults .MenuItems.MenuItems-Input.ui-autocomplete .ui-menu-item a`, {
        ...suggestedTextStyleHelper().option,
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        ...{
            ".Title": {
                ...searchResultsStyles.title,
                ...lineHeightAdjustment(),
                display: "block",
                width: percent(100),
                marginBottom: ".15em",
            },
            ".Aside": {
                display: "inline-block",
                float: "none",
                ...searchResultsStyles.meta,
            },
            ".Aside .Date": {
                display: "inline",
                ...searchResultsStyles.meta,
            },
            ".Gloss": {
                ...searchResultsStyles.excerpt,
                display: "block",
                paddingLeft: 0,
                marginTop: 0,
                width: percent(100),
            },
        },
    } as CSSObject);

    cssOut(`.Item-Search .Media .ImgExt`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
    });

    cssOut(`.Item-Search .Summary`, {
        marginTop: styleUnit(searchResultsVars.excerpt.margin),
    });

    const buttonWidth = 46;

    cssOut(`body.Section-SearchResults .AdvancedSearch .InputAndButton .Handle.Handle `, {
        right: styleUnit(buttonWidth),
    });
    cssOut(`body.Section-SearchResults .AdvancedSearch .InputAndButton .bwrap.bwrap`, {
        minWidth: styleUnit(buttonWidth),
    });

    cssOut(`body.Section-SearchResults .AdvancedSearch .KeywordsWrap.InputAndButton .InputBox.InputBox`, {
        paddingRight: styleUnit(buttonWidth * 2 - 10),
    });
};
