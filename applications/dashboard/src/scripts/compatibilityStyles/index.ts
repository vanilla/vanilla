/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { negative } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, important } from "csx";
import { inputVariables } from "@library/forms/inputStyles";
import { linkMixin } from "@library/navigation/siteNavStyles";
import { socialConnectCSS } from "@dashboard/compatibilityStyles/socialConnectStyles";
import { reactionsCSS } from "@dashboard/compatibilityStyles/reactionsStyles";
import { buttonCSS } from "@dashboard/compatibilityStyles/buttonStylesCompat";
import { inputCSS } from "@dashboard/compatibilityStyles/inputStyles";
import { flyoutCSS } from "@dashboard/compatibilityStyles/flyoutStyles";
import { textLinkCSS } from "@dashboard/compatibilityStyles/textLinkStyles";
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
import { forumMetaCSS, metasCSS } from "@library/metas/Metas.compat.styles";
import { forumTagCSS } from "@library/metas/Tags.compat.styles";
import { signInMethodsCSS } from "@dashboard/compatibilityStyles/signInMethodStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { dropDownVariables } from "@library/flyouts/dropDownStyles";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { leaderboardCSS } from "@library/leaderboardWidget/LeaderboardCompat.styles";
import { cssOut } from "./cssOut";
import { pageBoxCompatStyles } from "@dashboard/compatibilityStyles/PageBox.compat.styles";
import { profileCompatCSS } from "@dashboard/compatibilityStyles/pages/Profile.compat.styles";
import { discussionCompatCSS } from "@dashboard/compatibilityStyles/pages/Discussion.compat.styles";
import { categoryListCompatCSS } from "@dashboard/compatibilityStyles/pages/CategoryList.compat.styles";
import { conversationListCompatCSS } from "@dashboard/compatibilityStyles/pages/ConversationList.compat.styles";
import { conversationCompatCSS } from "@dashboard/compatibilityStyles/pages/Conversation.compat.styles";
import { discussionListCompatCSS } from "@library/features/discussions/DiscussionList.compat.styles";
import { widgetLayoutCompactCSS } from "@library/layout/WidgetLayout.compat.styles";
import { onlineUserWrapCSS } from "@dashboard/compatibilityStyles/onlineUserStyles";
import { bodyStyleMixin } from "@library/layout/bodyStyles";
import { userContentCompatCSS } from "@library/content/UserContent.compat.styles";
export { cssOut };

// Re-export for compatibility.
export { trimTrailingCommas } from "./trimTrailingCommas";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export let compatibilityStyles: () => void;
compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = vars.mainColors;
    const fg = ColorsUtils.colorOut(mainColors.fg);
    const fgHeading = ColorsUtils.colorOut(mainColors.fgHeading);
    const bg = ColorsUtils.colorOut(mainColors.bg);
    const primary = ColorsUtils.colorOut(mainColors.primary);
    const userPhotoVars = userPhotoVariables();

    fullBackgroundCompat();

    cssOut("body", {
        ...bodyStyleMixin(),
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

    cssOut(".Box h4", { color: fgHeading });

    const panelSelectors = `
        .About a,
        .Panel.Panel-main .PanelInfo a.ItemLink
        `;

    // Panel
    cssOut(panelSelectors, {
        ...linkMixin(undefined, true, panelSelectors),
        padding: 0,
        minHeight: 0,
        display: "flex",
        opacity: 1,
        ...Mixins.linkDecoration(),
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

            ".Count": {
                textDecoration: "none",
                display: "inline-block",
            },
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
            ...Mixins.font({
                ...vars.fontSizeAndWeightVars("large"),
                color: fg,
            }),
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

    cssOut(`.DataList .Item:last-child, .MessageList .Item:last-child`, {
        borderTopColor: ColorsUtils.colorOut(vars.border.color),
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

    cssOut(".Bullet, .QuickSearch", {
        display: "none",
    });

    cssOut(".suggestedTextInput-option", suggestedTextStyleHelper().option);

    cssOut(`.DataList .Item .Options .OptionsMenu`, {
        order: 10, // we want it last
    });

    cssOut(".selectBox-item .selectBox-selectedIcon", {
        color: ColorsUtils.colorOut(dropDownVariables().contents.fg),
    });

    cssOut(`.HomepageTitle, .pageNotFoundTitle`, {
        ...Mixins.font({
            ...vars.fontSizeAndWeightVars("largeTitle", "bold"),
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
    widgetLayoutCompactCSS();
    blockColumnCSS();
    buttonCSS();
    flyoutCSS();
    textLinkCSS();
    forumTagCSS();
    inputCSS();
    socialConnectCSS();
    reactionsCSS();
    paginationCSS();
    forumLayoutCSS();
    metasCSS();
    forumMetaCSS();
    fontCSS();
    categoriesCSS();
    bestOfCSS();
    ideaCSS();
    tableCSS();
    searchPageCSS();
    groupsCSS();
    profileCompatCSS();
    discussionCompatCSS();
    userContentCompatCSS();
    discussionListCompatCSS();
    categoryListCompatCSS();
    conversationListCompatCSS();
    conversationCompatCSS();
    photoGridCSS();
    messagesCSS();
    signaturesCSS();
    signInMethodsCSS();
    leaderboardCSS();
    onlineUserWrapCSS();
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
