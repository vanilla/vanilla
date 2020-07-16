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
import {
    absolutePosition,
    borders,
    importantUnit,
    margins,
    negative,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
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
import { blockColumnCSS } from "@dashboard/compatibilityStyles/blockColumnStyles";
import { signaturesCSS } from "./signaturesSyles";
import { searchResultsVariables } from "@vanilla/library/src/scripts/features/search/searchResultsStyles";
import { forumTagCSS } from "@dashboard/compatibilityStyles/forumTagStyles";
import { signInMethodsCSS } from "@dashboard/compatibilityStyles/signInMethodStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { dropDownVariables } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import { logDebugConditionnal } from "@vanilla/utils";
import { forumVariables } from "@library/forums/forumStyleVars";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export let compatibilityStyles: () => void;
compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const formVars = forumVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = vars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const primaryContrast = colorOut(mainColors.primaryContrast);

    fullBackgroundCompat();

    cssOut("body", {
        backgroundColor: bg,
        color: fg,
    });

    cssOut(".Frame", {
        background: "none",
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
        color: fg,
    });

    cssOut(".ReactButton.PopupWindow&:hover .Sprite::before", {
        color: primary,
    });

    cssOut(".Box h4", { color: fg });

    const panelSelectors = `
        .About a,
        .Panel.Panel-main .PanelInfo a.ItemLink,
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
            fontSize: unit(vars.fonts.size.large),
        },
    );

    cssOut(
        `
            .MessageList .Item:not(.Read) .Title,
            .DataList .Item:not(.Read) .Title,
    `,
        {
            $nest: {
                "&&": {
                    fontWeight: vars.fonts.weights.bold,
                },
            },
        },
    );

    cssOut(
        `
            .MessageList .Item.Read .Title,
            .DataList .Item.Read .Title,
    `,
        {
            $nest: {
                "&&": {
                    fontWeight: vars.fonts.weights.normal,
                },
            },
        },
    );

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

    cssOut(
        `
        .DataList .Item,
        .DataList .Empty,
    `,
        {
            borderTop: singleBorder(),
            borderBottom: singleBorder(),
            ...paddings(resultVars.spacing.padding),
            ...margins(formVars.lists.spacing.margin),
            backgroundColor: colorOut(formVars.lists.colors.bg),
        },
    );

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
        ...borders(vars.borderType.dropDowns),
        overflow: "hidden",
    });

    cssOut(`.Frame-content`, {
        marginTop: unit(layoutVars.main.topSpacing - vars.gutter.size),
    });

    cssOut(`.Content .PageControls`, {
        marginBottom: unit(24),
    });

    cssOut(`.DataList .Item:last-child, .MessageList .Item:last-child`, {
        borderTopColor: colorOut(vars.border.color),
    });

    cssOut(`.Author a:not(.PhotoWrap), .Author .${userCardClasses().link}`, {
        fontWeight: vars.fonts.weights.bold,
    });

    cssOut(`.DataList.Discussions .Item .Title`, {
        width: `100%`,
    });

    cssOut(`.DataList.Discussions .Item .Title a`, {
        textDecoration: important("none"),
    });

    cssOut(`.Container .DataList .Meta .Tag-Announcement`, {
        opacity: 1,
    });

    cssOut(".Panel > * + *", {
        marginTop: unit(24),
    });

    cssOut(`.Panel > .PhotoWrapLarge`, {
        padding: 0,
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
        backgroundColor: colorOut(vars.mainColors.primaryContrast),
        color: colorOut(vars.mainColors.primary),
        ...borders({
            radius: 2,
            color: vars.mainColors.primary,
        }),
        ...paddings({
            vertical: 3,
            horizontal: 6,
        }),
    });

    cssOut(`.Item.Read`, {
        backgroundColor: colorOut(formVars.lists.colors.read.bg),
        opacity: 1,
        $nest: {
            "&:hover, &:focus, &:active, &.focus-visible": {
                backgroundColor: colorOut(formVars.lists.colors.read.bg),
                opacity: 1,
            },
        },
    });

    cssOut(".Bullet, .QuickSearch", {
        display: "none",
    });

    cssOut(".suggestedTextInput-option", suggestedTextStyleHelper().option);

    cssOut(`.DataList .Item .Options .OptionsMenu`, {
        order: 10, // we want it last
    });

    cssOut(`.Breadcrumbs`, {
        ...paddings({
            vertical: vars.gutter.half,
        }),
    });

    cssOut(".selectBox-item .selectBox-selectedIcon", { color: colorOut(dropDownVariables().item.colors.fg) });

    cssOut(`.HomepageTitle, .pageNotFoundTitle`, {
        ...fonts({
            size: vars.fonts.size.largeTitle,
            weight: vars.fonts.weights.bold,
        }),
    });

    blockColumnCSS();
    buttonCSS();
    flyoutCSS();
    textLinkCSS();
    forumTagCSS();
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
    signInMethodsCSS();
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

export const nestedWorkaround = (selector: string, nestedObject, debug?: boolean) => {
    // $nest doesn't work in this scenario. Working around it by doing it manually.
    // Hopefully a future update will allow us to just pass the nested styles in the cssOut above.

    if (nestedObject) {
        let rawStyles = `\n`;
        Object.keys(nestedObject).forEach(key => {
            const finalSelector = `${selector}${key.replace(/^&+/, "")}`;
            let newStyleDeclaration = "";
            if (selector !== "") {
                const targetStyles = nestedObject[key];
                const styleProps = targetStyles ? Object.keys(targetStyles) : [];

                let emptyStyles = true;

                if (styleProps.length > 0) {
                    newStyleDeclaration += `${finalSelector} { `;
                    styleProps.forEach(property => {
                        const style = targetStyles[property];
                        if (style !== undefined && style !== "") {
                            newStyleDeclaration += `\n    ${camelCaseToDash(property)}: ${
                                style instanceof ColorHelper ? colorOut(style) : style
                            };`;
                            emptyStyles = false;
                        }
                    });
                    newStyleDeclaration += `\n}\n\n`;
                }

                if (!emptyStyles) {
                    rawStyles += newStyleDeclaration;
                }
            }
        });
        logDebugConditionnal(debug, rawStyles);
        cssRaw(rawStyles);
    }
};
