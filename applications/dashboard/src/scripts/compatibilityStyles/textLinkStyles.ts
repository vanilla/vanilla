/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    colorOut,
    ILinkColorOverwrites,
    ILinkSpecialOverwritesOptional,
    setAllLinkStateStyles,
} from "@library/styles/styleHelpers";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { globalVariables } from "@library/styles/globalStyleVars";

export const textLinkCSS = () => {
    const globalVars = globalVariables();

    // Various links
    mixinLink(".Navigation-linkContainer a");
    mixinLink(".Panel .PanelInThisDiscussion a");
    mixinLink(".Panel .Leaderboard a");
    mixinLink(".Panel .InThisConversation a");
    mixinLink(".FieldInfo a");

    mixinLink("div.Popup .Body a");
    mixinLink(".followButton");
    mixinLink(".SelectWrapper::after");
    mixinLink(".Back a");
    mixinLink(".OptionsLink-Clipboard");
    mixinLink("a.OptionsLink");
    mixinLink("a.MoreWrap, .MoreWrap a, .MorePager a, .more.More, .MoreWrap a.more.More");
    mixinLink(`body.Section-BestOf .Tile .Message a`);
    mixinLink(
        `
        .DataList .IdeationTag,
        .DataList .tag-tracker,
        .DataList .MItem.RoleTracker,
        .MessageList .IdeationTag,
        .MessageList .tag-tracker,
        .MessageList .MItem.RoleTracker,
        .DataTableWrap .IdeationTag,
        .DataTableWrap .tag-tracker,
        .DataTableWrap .MItem.RoleTracker
        `,
    );
    mixinLink(`
        .Container .userContent a,
        .Container .UserContent a
    `);
    mixinLink(".BreadcrumbsBox .Breadcrumbs a", {
        default: globalVars.links.colors.default,
    });

    // Links that have FG color by default but regular state colors.
    mixinLinkNoDefaultLinkAppearance(".ItemContent a");
    mixinLinkNoDefaultLinkAppearance(".DataList .Item h3 a");
    mixinLinkNoDefaultLinkAppearance(".DataList .Item a.Title");
    mixinLinkNoDefaultLinkAppearance(".DataList .Item .Title a");
    mixinLinkNoDefaultLinkAppearance(".MenuItems a");
    mixinLinkNoDefaultLinkAppearance(".DataTable h2 a");
    mixinLinkNoDefaultLinkAppearance(".DataTable h3 a");
    mixinLinkNoDefaultLinkAppearance(".DataTable .Title.Title a");
    mixinLinkNoDefaultLinkAppearance(".Timebased.EndTime a");
    mixinLinkNoDefaultLinkAppearance(".FilterMenu a");
    mixinLinkNoDefaultLinkAppearance(`.DataList#search-results .Breadcrumbs a`);
    mixinLinkNoDefaultLinkAppearance(`.selectBox-toggle`);

    mixinLinkNoDefaultLinkAppearance(`
        .Container .Frame-contentWrap .ChildCategories,
        .Container .Frame-contentWrap .ChildCategories a,
        .DiscussionName .Wrap > a,`);
};

// Mixins replacement
export const mixinLink = (
    selector: string,
    linkColorOverwrites?: ILinkColorOverwrites,
    nonstandardLinkStates?: ILinkSpecialOverwritesOptional,
) => {
    selector = trimTrailingCommas(selector);
    const selectors = selector.split(",");
    const linkColors = setAllLinkStateStyles(linkColorOverwrites, nonstandardLinkStates);
    if (!selectors) {
        if (linkColors.color !== undefined) {
            cssOut(selector, {
                color: colorOut(linkColors.color),
            });
        }
        nestedWorkaround(trimTrailingCommas(selector), linkColors.nested);
    } else {
        selectors.map(s => {
            if (linkColors.color !== undefined) {
                cssOut(selector, {
                    color: colorOut(linkColors.color),
                });
            }
            nestedWorkaround(trimTrailingCommas(s), linkColors.nested);
        });
    }
};

export const mixinLinkNoDefaultLinkAppearance = selector => {
    const globalVars = globalVariables();
    mixinLink(selector, { default: globalVars.mainColors.fg });
};
