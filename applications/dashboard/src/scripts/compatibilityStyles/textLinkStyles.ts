/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { mixinClickInput } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const textLinkCSS = () => {
    const globalVars = globalVariables();

    // Various links
    mixinClickInput(".Navigation-linkContainer a");
    mixinClickInput(".Panel .PanelInThisDiscussion a");
    mixinClickInput(".Panel .Leaderboard a");
    mixinClickInput(".Panel .InThisConversation a");
    mixinClickInput(".FieldInfo a");

    mixinClickInput("div.Popup .Body a");
    mixinClickInput(".selectBox-toggle");
    mixinClickInput(".followButton");
    mixinClickInput(".SelectWrapper::after");
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
        .Container .userContent a,
        .Container .UserContent a
    `);
    mixinClickInput(".BreadcrumbsBox .Breadcrumbs a", {
        default: globalVars.links.colors.default,
    });
    mixinClickInput(".DataList .Item .Title a");

    // Links that have FG color by default but regular state colors.
    mixinTextLinkNoDefaultLinkAppearance(".ItemContent a");
    mixinTextLinkNoDefaultLinkAppearance(".DataList .Item h3 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataList .Item a.Title");

    mixinTextLinkNoDefaultLinkAppearance(".MenuItems a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable h2 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable h3 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable .Title.Title a");
    mixinTextLinkNoDefaultLinkAppearance(".Timebased.EndTime a");
    mixinTextLinkNoDefaultLinkAppearance(".FilterMenu a");
    mixinTextLinkNoDefaultLinkAppearance(`.DataList#search-results .Breadcrumbs a`);
    mixinTextLinkNoDefaultLinkAppearance(`.Container a.UserLink`);
    mixinTextLinkNoDefaultLinkAppearance(`.DataTable a.CommentDate`);
    mixinTextLinkNoDefaultLinkAppearance(`.DataTable.DiscussionsTable a.Title`);
};

export const mixinTextLinkNoDefaultLinkAppearance = selector => {
    const globalVars = globalVariables();
    mixinClickInput(selector, { default: globalVars.mainColors.fg, textDecoration: "none" });
};
