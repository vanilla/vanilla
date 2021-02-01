/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { importantUnit, negative, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, ColorHelper, important } from "csx";
import { inputVariables } from "@vanilla/library/src/scripts/forms/inputStyles";
import { siteNavNodeClasses } from "@vanilla/library/src/scripts/navigation/siteNavStyles";
import { socialConnectCSS } from "@dashboard/compatibilityStyles/socialConnectStyles";
import { reactionsCSS } from "@dashboard/compatibilityStyles/reactionsStyles";
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
import { searchPageCSS } from "./searchPageStyles";
import { groupsCSS } from "@dashboard/compatibilityStyles/groupsStyles";
import { photoGridCSS } from "@dashboard/compatibilityStyles/photoGridStyles";
import { messagesCSS } from "@dashboard/compatibilityStyles/messagesStyles";
import { blockColumnCSS } from "@dashboard/compatibilityStyles/blockColumnStyles";
import { signaturesCSS } from "./signaturesSyles";
import { searchResultsVariables } from "@vanilla/library/src/scripts/features/search/searchResultsStyles";
import { forumTagCSS } from "@dashboard/compatibilityStyles/forumTagStyles";
import { signInMethodsCSS } from "@dashboard/compatibilityStyles/signInMethodStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { dropDownVariables } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import { forumVariables } from "@library/forums/forumStyleVars";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { leaderboardCSS } from "@dashboard/compatibilityStyles/Leaderboard.styles";
import { cssOut } from "./cssOut";
import { pageBoxCompatStyles } from "@dashboard/compatibilityStyles/PageBox.compat.styles";
import { profileCompatCSS } from "@dashboard/compatibilityStyles/pages/Profile.compat.styles";
import { discussionCompatCSS } from "@dashboard/compatibilityStyles/pages/Discussion.compat.styles";
import { discussionListCompatCSS } from "@dashboard/compatibilityStyles/pages/DiscussionList.compat.styles";
import { categoryListCompatCSS } from "@dashboard/compatibilityStyles/pages/CategoryList.compat.styles";
import { conversationListCompatCSS } from "@dashboard/compatibilityStyles/pages/ConversationList.compat.styles";
import { conversationCompatCSS } from "@dashboard/compatibilityStyles/pages/Conversation.compat.styles";
export { cssOut };

// Re-export for compatibility.
export { trimTrailingCommas } from "./trimTrailingCommas";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export let compatibilityStyles: () => void;
compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const formVars = forumVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = vars.mainColors;
    const fg = ColorsUtils.colorOut(mainColors.fg);
    const bg = ColorsUtils.colorOut(mainColors.bg);
    const primary = ColorsUtils.colorOut(mainColors.primary);
    const userPhotoVars = userPhotoVariables();

    fullBackgroundCompat();

    cssOut("body", {
        backgroundColor: bg,
        color: fg,
    });

    cssOut(".Frame", {
        background: "none",
    });

    // @mixin font-style-base()
    cssOut("html, body, .DismissMessage", {
        ...Mixins.font({
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
            ...Mixins.margin({
                all: 0,
                left: "auto",
            }),
            ...Mixins.padding({
                all: 0,
                left: styleUnit(12),
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
            fontSize: styleUnit(vars.fonts.size.large),
        },
    );

    cssOut(
        `
            .MessageList .Item:not(.Read) .Title,
            .DataList .Item:not(.Read) .Title,
            .DataTable .Item:not(.Read) .Title
    `,
        {
            fontWeight: vars.fonts.weights.bold,
        },
    );

    cssOut(
        `
            .MessageList .Item.Read .Title,
            .DataList .Item.Read .Title,
            .DataTable .Item.Read .Title
    `,
        {
            fontWeight: vars.fonts.weights.normal,
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
            ...Mixins.border(inputVariables().border),
            borderTopRightRadius: 0,
            borderBottomRightRadius: 0,
        },
    );

    cssOut(`div.Popup .Body`, {
        // borderRadius: unit(vars.border.radius),
        ...Mixins.border(vars.borderType.modals),
        backgroundColor: bg,
        color: fg,
    });

    // Items
    const resultVars = searchResultsVariables();

    cssOut(
        `
        .DataList .Item,
        .DataList .Empty,
    `,
        {
            borderTop: singleBorder(),
            borderBottom: singleBorder(),
            ...Mixins.padding(resultVars.spacing.padding),
            ...Mixins.margin(formVars.lists.spacing.margin),
            backgroundColor: ColorsUtils.colorOut(formVars.lists.colors.bg),
        },
    );

    cssOut(`.DataList .Item + .Item`, {
        borderTop: "none",
    });

    cssOut(`.DataList .Item ~ .CategoryHeading::before, .MessageList .Item ~ .CategoryHeading::before`, {
        marginTop: styleUnit(vars.gutter.size * 2.5),
        border: "none",
    });

    cssOut(`div.Popup p`, {
        paddingLeft: 0,
        paddingRight: 0,
    });

    cssOut(`.Herobanner-bgImage`, {
        WebkitFilter: "none",
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
            color: ColorsUtils.colorOut(vars.links.colors.hover),
        },
    );

    cssOut(
        `
        a.TextColor, a .TextColor`,

        {
            color: ColorsUtils.colorOut(vars.mainColors.fg),
        },
    );

    cssOut(".ButtonGroup .Dropdown", {
        marginTop: styleUnit(negative(vars.border.width)),
    });

    cssOut(`.QuickSearchButton`, {
        color: fg,
        ...Mixins.border(vars.borderType.formElements.buttons),
    });

    cssOut(`.Frame-contentWrap`, {
        marginTop: styleUnit(layoutVars.main.topSpacing - vars.gutter.size),
    });

    cssOut(`.Content .PageControls`, {
        marginBottom: styleUnit(24),
    });

    cssOut(`.DataList .Item:last-child, .MessageList .Item:last-child`, {
        borderTopColor: ColorsUtils.colorOut(vars.border.color),
    });

    cssOut(`.Author a:not(.PhotoWrap), .Author .${userCardClasses().link}`, {
        fontWeight: vars.fonts.weights.bold,
    });

    cssOut(`.DataList.Discussions .Item .Title`, {
        width: calc(`100% - ${styleUnit(vars.icon.sizes.default * 2 + vars.gutter.quarter)}`),
    });

    cssOut(`.DataList.Discussions .Item .Title a`, {
        textDecoration: "none",
    });

    cssOut(`.DataList.Discussions .Item .Options`, {
        position: "absolute",
        right: styleUnit(layoutVars.cell.paddings.horizontal),
        top: styleUnit(layoutVars.cell.paddings.vertical),
    });

    cssOut(`.Container .DataList .Meta .Tag-Announcement`, {
        opacity: 1,
    });

    cssOut(".Panel > * + *", {
        marginTop: styleUnit(24),
    });

    cssOut(`.Panel > .PhotoWrapLarge`, {
        padding: 0,
    });

    cssOut(".Panel li a", {
        minHeight: 0,
    });

    cssOut(".Panel .Box li + li", {
        paddingTop: forumFontsVariables().panelLink.spacer.default,
    });

    cssOut(`#ConversationForm label`, {
        color: ColorsUtils.colorOut(vars.mainColors.fg),
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
        backgroundColor: ColorsUtils.colorOut(vars.mainColors.primaryContrast),
        color: ColorsUtils.colorOut(vars.mainColors.primary),
        ...Mixins.border({
            radius: 2,
            color: vars.mainColors.primary,
        }),
        ...Mixins.padding({
            vertical: 3,
            horizontal: 6,
        }),
    });

    cssOut(`.Item.Read`, {
        backgroundColor: ColorsUtils.colorOut(formVars.lists.colors.read.bg),
        opacity: 1,
        ...{
            "&:hover, &:focus, &:active, &.focus-visible": {
                backgroundColor: ColorsUtils.colorOut(formVars.lists.colors.read.bg),
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
        ...Mixins.padding({
            vertical: vars.gutter.half,
        }),
    });

    cssOut(".selectBox-item .selectBox-selectedIcon", {
        color: ColorsUtils.colorOut(dropDownVariables().item.colors.fg),
    });

    cssOut(`.HomepageTitle, .pageNotFoundTitle`, {
        ...Mixins.font({
            size: vars.fonts.size.largeTitle,
            weight: vars.fonts.weights.bold,
        }),
    });

    cssOut(`.DataList .PhotoWrap, .MessageList .PhotoWrap`, {
        width: styleUnit(userPhotoVars.sizing.medium),
        height: styleUnit(userPhotoVars.sizing.medium),
    });

    cssOut(`.LocaleOptions`, {
        textAlign: "center",
    });

    pageBoxCompatStyles();
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
    searchPageCSS();
    groupsCSS();
    profileCompatCSS();
    discussionCompatCSS();
    discussionListCompatCSS();
    categoryListCompatCSS();
    conversationListCompatCSS();
    conversationCompatCSS();
    photoGridCSS();
    messagesCSS();
    signaturesCSS();
    signInMethodsCSS();
    leaderboardCSS();
});

export const mixinCloseButton = (selector: string) => {
    const vars = globalVariables();
    cssOut(selector, {
        color: ColorsUtils.colorOut(vars.mainColors.fg),
        background: "none",
        ...{
            "&:hover": {
                color: ColorsUtils.colorOut(vars.mainColors.primary),
            },
            "&:focus": {
                color: ColorsUtils.colorOut(vars.mainColors.primary),
            },
            "&.focus-visible": {
                color: ColorsUtils.colorOut(vars.mainColors.primary),
            },
        },
    });
};
