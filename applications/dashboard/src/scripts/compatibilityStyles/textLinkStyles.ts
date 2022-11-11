/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { mixinClickInput } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { Mixins } from "@library/styles/Mixins";
import { breadcrumbsVariables } from "@library/navigation/breadcrumbsStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { injectGlobal } from "@emotion/css";
import { mixinListItemTitleLink } from "@library/lists/ListItem.styles";

export const textLinkCSS = () => {
    const globalVars = globalVariables();
    const vars = breadcrumbsVariables();

    // Various links
    mixinClickInput(".Navigation-linkContainer a");

    mixinClickInput(".Panel .Leaderboard a");
    mixinClickInput(".FieldInfo a");

    mixinClickInput("div.Popup .Body a");
    mixinClickInput(".selectBox-toggle");
    mixinClickInput(".followButton");
    mixinClickInput(".Back a");
    mixinClickInput(".OptionsLink-Clipboard");
    mixinClickInput("a.OptionsLink");
    mixinClickInput("a.MoreWrap, .MoreWrap a, .MorePager a, .more.More, .MoreWrap a.more.More");
    mixinClickInput(`body.Section-BestOf .Tile .Message a`);
    mixinClickInput(
        `
        .DataList .IdeationTag,
        .DataList .MItem.RoleTracker,
        .MessageList .IdeationTag,
        .MessageList .tag-tracker,
        .DataTableWrap .IdeationTag,
        .DataTableWrap .tag-tracker,
        .DataTableWrap .MItem.RoleTracker
        `,
    );
    mixinClickInput(`
        .userContent a,
        .UserContent a
    `);
    mixinClickInput(".BreadcrumbsBox .Breadcrumbs a", {
        default: globalVars.links.colors.default,
    });
    mixinClickInput("body.Section-Entry label.RadioLabel a, body.Section-Entry label.CheckBoxLabel a");

    // Links that have FG color by default but regular state colors.
    mixinTextLinkNoDefaultLinkAppearance(".ItemContent a");
    mixinTextLinkNoDefaultLinkAppearance(`
        .Content .DataList .DiscussionMeta a,
        .Content .DataList .CommentMeta a,
        .Content .DataList-Search a,
        .Content .Breadcrumbs a,
        .Content .MessageList .DiscussionMeta a,
        .Content .MessageList .CommentMeta a,
        .Content .Container .Frame-contentWrap .ChildCategories a,
        .Content .Item.Application .Meta a,
        .Content .Meta.Group-Meta.Group-Info a
    `);

    injectGlobal({
        [`
            .DataList .Item .Title a,
            .DateList Item h3 a,
            .DataList .Item a.Title,
            .DataTable .Title.Title a,
            .DataTable h3 a,
            .DataTable h2 a,
            .DataTable.DiscussionsTable a.Title
        `]: mixinListItemTitleLink(),
    });

    mixinTextLinkNoDefaultLinkAppearance(".MenuItems a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable .Title.Title a");
    mixinTextLinkNoDefaultLinkAppearance(".Timebased.EndTime a");
    mixinTextLinkNoDefaultLinkAppearance(".FilterMenu a");
    mixinTextLinkNoDefaultLinkAppearance(".Box.SideMenu .PanelInfo li a");
    mixinTextLinkNoDefaultLinkAppearance(`.DataList#search-results .Breadcrumbs a`);
    mixinTextLinkNoDefaultLinkAppearance(`.Container a.UserLink`);
    mixinTextLinkNoDefaultLinkAppearance(`.DataTable a.CommentDate`);
    mixinTextLinkNoDefaultLinkAppearance(`.DataTable .Meta .MItem`);
    mixinTextLinkNoDefaultLinkAppearance(`.Panel .InThisConversation a`);
    mixinTextLinkNoDefaultLinkAppearance(`.Panel .PanelInThisDiscussion a`);
    mixinTextLinkNoDefaultLinkAppearance(".ShowTags a");

    injectGlobal({
        [`.Panel.Panel-main .PanelInfo.PanelInThisDiscussion .Aside`]: {
            paddingLeft: 0,
            paddingRight: "1ex",
            display: "inline",
        },
    });

    injectGlobal({
        [`.Panel.Panel-main .PanelInfo.PanelInThisDiscussion .Username`]: {
            fontWeight: globalVars.fonts.weights.semiBold,
        },
    });

    injectGlobal({
        [`.BreadcrumbsBox .Breadcrumbs a`]: {
            marginRight: "0.5ex",
            color: ColorsUtils.colorOut(vars.link.font.color),
            ...Mixins.font(vars.link.font),
        },
    });

    injectGlobal({
        [`.BreadcrumbsBox  .Crumb`]: {
            marginLeft: vars.separator.spacing,
            marginRight: vars.separator.spacing,
            ...Mixins.font(vars.separator.font),
        },
    });
};

export const mixinTextLinkNoDefaultLinkAppearance = (selector) => {
    const globalVars = globalVariables();
    mixinClickInput(selector, { default: globalVars.mainColors.fg, textDecoration: "none" });
};
