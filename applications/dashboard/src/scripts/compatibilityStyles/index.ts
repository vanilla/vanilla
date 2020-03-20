/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { cssRaw, cssRule, media } from "typestyle";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpersColors";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { fonts } from "@library/styles/styleHelpersTypography";
import { borders, importantUnit, margins, negative, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { calc, ColorHelper, important } from "csx";
import { inputVariables } from "@vanilla/library/src/scripts/forms/inputStyles";
import { siteNavNodeClasses } from "@vanilla/library/src/scripts/navigation/siteNavStyles";
import { socialConnectCSS } from "@dashboard/compatibilityStyles/socialConnectStyles";
import { reactionsCSS } from "@dashboard/compatibilityStyles/reactionsStyles";
import * as types from "typestyle/lib/types";
import { buttonCSS } from "@dashboard/compatibilityStyles/buttonStylesCompat";
import { inputCSS } from "@dashboard/compatibilityStyles/inputStyles";
import { flyoutCSS } from "@dashboard/compatibilityStyles/flyoutStyles";
import { textLinkCSS } from "@dashboard/compatibilityStyles/textLinkStyles";
import { forumMetaCSS } from "@dashboard/compatibilityStyles/forumMetaStyles";
import { paginationCSS } from "@dashboard/compatibilityStyles/paginationStyles";
import { fontCSS, forumFontsVariables } from "./fontStyles";
import { forumLayoutCSS, forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { categoriesCSS } from "@dashboard/compatibilityStyles/categoriesStyles";
import { bestOfCSS } from "@dashboard/compatibilityStyles/bestOfStyles";
import { ideaCSS } from "@dashboard/compatibilityStyles/ideaStyles";
import { tableCSS } from "@dashboard/compatibilityStyles/tableStyles";
import { discussionCSS } from "./discussionStyles";
import { searchPageCSS } from "./searchPageStyles";
import { groupsCSS } from "@dashboard/compatibilityStyles/groupsStyles";
import { profilePageCSS } from "@dashboard/compatibilityStyles/profilePageSyles";
import { photoGridCSS } from "@dashboard/compatibilityStyles/photoGridStyles";
import { messagesCSS } from "@dashboard/compatibilityStyles/messagesStyles";
import { signaturesCSS } from "./signaturesSyles";
import { searchResultsVariables } from "@vanilla/library/src/scripts/features/search/searchResultsStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = vars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);

    fullBackgroundCompat();

    cssOut("body", {
        backgroundColor: bg,
        color: fg,
    });

    cssOut(".Frame", {
        background: "none",
        overflow: "auto",
    });

    cssOut(
        `
        .Content .DataList .Item:not(.ItemDiscussion):not(.ItemComment),
        .Content .MessageList .Item.Item:not(.ItemDiscussion):not(.ItemComment)
        `,
        {
            background: "none",
            borderColor: colorOut(vars.border.color),
            ...paddings(layoutVars.cell.paddings),
        },
    );

    // @mixin font-style-base()
    cssOut("html, body, .DismissMessage", {
        ...fonts({
            family: vars.fonts.families.body,
            color: mainColors.fg,
        }),
    });

    cssOut(
        ".DismissMessage, .DataList .Excerpt, .DataList .CategoryDescription, .MessageList .Excerpt, .MessageList .CategoryDescription",
        {
            color: fg,
        },
    );

    cssOut(`.DataTable .Item td, .Item .Poll .PollOption`, {
        background: bg,
        color: fg,
    });

    cssOut(".ReactButton.PopupWindow&:hover .Sprite::before", {
        color: primary,
    });

    cssOut(`a.Bookmark`, {
        opacity: 1,
        $nest: {
            "&::before": {
                color: primary,
            },
            "&:hover::before": {
                color: colorOut(mainColors.secondary),
            },
        },
    });

    cssOut(
        `
        .Content a.Bookmarked::before,
        .Content a.Bookmark::before,
        .Content a.Bookmarking::before
        `,
        {
            color: important(colorOut(mainColors.fg.fade(0.5)) as string),
        },
    );

    cssOut(".Box h4", { color: fg });

    cssOut(`.CategoryBox > .OptionsMenu`, {
        marginRight: unit(layoutVars.cell.paddings.horizontal),
    });

    const panelSelectors = `
        .About a,
        .Panel.Panel-main .FilterMenu a,
        .Panel.Panel-main .BoxFilter a,
        .Panel.Panel-main .PanelInfo a.ItemLink,
        .Panel.Panel-main .FilterMenu a,
        `;

    // Panel
    cssOut(panelSelectors, {
        ...siteNavNodeClasses().linkMixin(true, panelSelectors),
        minHeight: 0,
        display: "flex",
        opacity: 1,
    });

    cssOut(".Panel .ClearFix::after", {
        display: important("none"),
    });

    cssOut("a", {
        cursor: "pointer",
    });

    cssOut(
        `
        #Panel.Panel-main .FilterMenu .Aside,
        .Panel.Panel-main .PanelInfo .Aside,
        .Item .Aside
        `,
        {
            ...margins({
                all: 0,
                left: "auto",
            }),
            ...paddings({
                all: 0,
                left: unit(12),
            }),
        },
    );

    cssOut(
        `
        a.Title,
        .Title a,
        .DataList .Item .Title,
        .DataList .Item.Read .Title,
        .DataList .Item h3,
        .MessageList .Item .Title,
        .MessageList .Item.Read .Title,
        .MessageList .Item h3
        .MenuItems a
        `,
        {
            color: fg,
        },
    );

    cssOut(".Pager > a.Highlight, .Pager > a.Highlight:focus, .Pager > a.Highlight:hover", {
        color: primary,
    });

    cssOut(
        `
        .Herobanner .SearchBox .AdvancedSearch .BigInput,
        .Herobanner .SearchBox #Form_Search
    `,
        {
            borderRight: 0,
            backgroundColor: bg,
            color: fg,
            ...borders(inputVariables().border),
            borderTopRightRadius: 0,
            borderBottomRightRadius: 0,
        },
    );

    cssOut(`div.Popup .Body`, {
        // borderRadius: unit(vars.border.radius),
        ...borders(vars.borderType.modals),
        backgroundColor: bg,
        color: fg,
    });

    // Items
    const resultVars = searchResultsVariables();
    const horizontalPadding = resultVars.spacing.padding.left + resultVars.spacing.padding.right;
    cssOut(`.DataList, .Item-Header`, {
        marginLeft: unit(-resultVars.spacing.padding.left),
        width: calc(`100% + ${unit(horizontalPadding)}`),
    });

    cssOut(`.DataList .Item`, {
        borderTop: singleBorder(),
        borderBottom: singleBorder(),
        ...paddings(resultVars.spacing.padding),
    });

    cssOut(`.DataList .Item + .Item`, {
        borderTop: "none",
    });

    cssOut(`.DataList .Item ~ .CategoryHeading::before, .MessageList .Item ~ .CategoryHeading::before`, {
        marginTop: unit(vars.gutter.size * 2.5),
        border: "none",
    });

    cssOut(`div.Popup p`, {
        paddingLeft: 0,
        paddingRight: 0,
    });

    cssOut(`.Herobanner-bgImage`, {
        "-webkit-filter": "none",
        filter: "none",
    });

    cssOut(".Herobanner .SearchBox .AdvancedSearch .BigInput", {
        borderTopRightRadius: important(0),
        borderBottomRightRadius: important(0),
    });

    cssOut(
        `
        a:hover,
        a.TextColor:hover,
        a:hover .TextColor`,
        {
            color: colorOut(vars.links.colors.hover),
        },
    );

    cssOut(
        `
        a.TextColor, a .TextColor`,

        {
            color: colorOut(vars.mainColors.fg),
        },
    );

    cssOut(".ButtonGroup .Dropdown", {
        marginTop: unit(negative(vars.border.width)),
    });

    cssOut(`.QuickSearchButton`, {
        color: fg,
        ...borders(vars.borderType.formElements.buttons),
    });

    cssOut(`.DataList.CategoryList .Item[class*=Depth]`, {
        ...paddings({
            vertical: vars.gutter.size,
            horizontal: importantUnit(vars.gutter.half),
        }),
    });

    cssOut(".MenuItems, .Flyout.Flyout", {
        ...borders(vars.borderType.dropDowns.content),
    });

    cssOut(`.Frame-content`, {
        marginTop: unit(vars.gutter.size * 2),
    });

    cssOut(`.Content .PageControls`, {
        marginBottom: unit(24),
    });

    cssOut(`.DataList .Item:last-child, .MessageList .Item:last-child`, {
        borderTopColor: colorOut(vars.border.color),
    });

    cssOut(`.Author a:not(.PhotoWrap)`, {
        fontWeight: vars.fonts.weights.bold,
    });

    cssOut(`.DataList.Discussions .Item .Title a`, {
        textDecoration: important("none"),
    });

    cssOut(`.Container .DataList .Meta .Tag-Announcement`, {
        opacity: 1,
    });

    cssOut(
        `
        .Container a.UserLink,
        .Container a.UserLink.BlockTitle
    `,
        {
            fontWeight: vars.fonts.weights.bold,
        },
    );

    cssOut(".Panel > * + *", {
        marginTop: unit(vars.gutter.size),
    });

    cssOut(".Panel li a", {
        minHeight: 0,
    });

    cssOut(".Panel.Panel li + li", {
        paddingTop: forumFontsVariables().panelLink.spacer.default,
    });

    cssOut(`#ConversationForm label`, {
        color: colorOut(vars.mainColors.fg),
    });

    cssOut(`.Group-Box.Group-MembersPreview .PageControls .H`, {
        position: "relative",
    });

    cssOut(`#Panel .FilterMenu .Aside, .PanelInfo .Aside, .Item .Aside`, {
        float: "none",
        display: "block",
        margin: `0 0 14px`,
    });

    cssOut(`.HasNew`, {
        backgroundColor: colorOut(vars.mainColors.primary),
        color: colorOut(vars.mainColors.primaryContrast),
    });

    cssOut(`.Item.Read`, {
        background: "none",
    });

    buttonCSS();
    flyoutCSS();
    textLinkCSS();
    forumMetaCSS();
    inputCSS();
    socialConnectCSS();
    reactionsCSS();
    paginationCSS();
    forumLayoutCSS();
    fontCSS();
    categoriesCSS();
    bestOfCSS();
    ideaCSS();
    tableCSS();
    discussionCSS();
    searchPageCSS();
    groupsCSS();
    profilePageCSS();
    photoGridCSS();
    messagesCSS();
    signaturesCSS();
});

export const mixinCloseButton = (selector: string) => {
    const vars = globalVariables();
    cssOut(selector, {
        color: colorOut(vars.mainColors.fg),
        background: "none",
        $nest: {
            "&:hover": {
                color: colorOut(vars.mainColors.primary),
            },
            "&:focus": {
                color: colorOut(vars.mainColors.primary),
            },
            "&.focus-visible": {
                color: colorOut(vars.mainColors.primary),
            },
        },
    });
};

export const trimTrailingCommas = selector => {
    return selector.trim().replace(new RegExp("[,]+$"), "");
};

export const cssOut = (selector: string, ...objects: types.NestedCSSProperties[]) => {
    cssRule(trimTrailingCommas(selector), ...objects, { $unique: true });
};

export const camelCaseToDash = (str: string) => {
    return str.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
};

export const nestedWorkaround = (selector: string, nestedObject: {}) => {
    // $nest doesn't work in this scenario. Working around it by doing it manually.
    // Hopefully a future update will allow us to just pass the nested styles in the cssOut above.

    let rawStyles = `\n`;
    Object.keys(nestedObject).forEach(key => {
        const finalSelector = `${selector}${key.replace(/^&+/, "")}`;
        if (selector !== "") {
            const targetStyles = nestedObject[key];
            const keys = Object.keys(targetStyles);
            if (keys.length > 0) {
                rawStyles += `${finalSelector} { `;
                keys.forEach(property => {
                    const style = targetStyles[property];
                    if (style) {
                        rawStyles += `\n    ${camelCaseToDash(property)}: ${
                            style instanceof ColorHelper ? colorOut(style) : style
                        };`;
                    }
                });
                rawStyles += `\n}\n\n`;
            }
        }
    });

    cssRaw(rawStyles);
};
