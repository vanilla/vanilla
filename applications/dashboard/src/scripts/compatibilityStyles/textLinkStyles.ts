/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, clickableItemStates } from "@library/styles/styleHelpers";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { globalVariables } from "@library/styles/globalStyleVars";

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
    mixinClickInput(`
        .Container .userContent a,
        .Container .UserContent a
    `);
    mixinClickInput(".BreadcrumbsBox .Breadcrumbs a", {
        default: globalVars.links.colors.default,
    });

    // Links that have FG color by default but regular state colors.
    mixinTextLinkNoDefaultLinkAppearance(".ItemContent a");
    mixinTextLinkNoDefaultLinkAppearance(".DataList .Item h3 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataList .Item a.Title");
    mixinTextLinkNoDefaultLinkAppearance(".DataList .Item .Title a");
    mixinTextLinkNoDefaultLinkAppearance(".MenuItems a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable h2 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable h3 a");
    mixinTextLinkNoDefaultLinkAppearance(".DataTable .Title.Title a");
    mixinTextLinkNoDefaultLinkAppearance(".Timebased.EndTime a");
    mixinTextLinkNoDefaultLinkAppearance(".FilterMenu a");
    mixinTextLinkNoDefaultLinkAppearance(`.DataList#search-results .Breadcrumbs a`);
};

// Mixins replacement
export const mixinClickInput = (selector: string, overwrite?: {}) => {
    selector = trimTrailingCommas(selector);
    const selectors = selector.split(",");
    const linkColors = clickableItemStates(overwriteColors, overwriteSpecial);
    if (!selectors) {
        if (linkColors.color !== undefined) {
            cssOut(selector, {
                color: colorOut(linkColors.color),
            });
        }
        nestedWorkaround(trimTrailingCommas(selector), linkColors.$nest);
    } else {
        selectors.map(s => {
            if (linkColors.color !== undefined) {
                cssOut(selector, {
                    color: colorOut(linkColors.color),
                });
            }
            nestedWorkaround(trimTrailingCommas(s), linkColors.$nest);
        });
    }
};

export const mixinTextLinkNoDefaultLinkAppearance = selector => {
    const globalVars = globalVariables();
    mixinClickInput(selector, { default: globalVars.mainColors.fg, textDecoration: "none" });
};
