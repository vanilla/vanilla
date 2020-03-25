/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ILinkColorOverwritesWithOptions, setAllLinkColors } from "@library/styles/styleHelpers";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { throwError } from "rxjs";
import { globalVariables } from "@library/styles/globalStyleVars";

export const textLinkCSS = () => {
    const globalVars = globalVariables();

    // Various links
    mixinTextLink(".Navigation-linkContainer a");
    mixinTextLink(".Panel .PanelInThisDiscussion a");
    mixinTextLink(".Panel .Leaderboard a");
    mixinTextLink(".Panel .InThisConversation a");
    mixinTextLink(".FieldInfo a");

    mixinTextLink("div.Popup .Body a");
    mixinTextLink(".selectBox-toggle");
    mixinTextLink(".followButton");
    mixinTextLink(".SelectWrapper::after");
    mixinTextLink(".Back a");
    mixinTextLink(".OptionsLink-Clipboard");
    mixinTextLink("a.OptionsLink");
    mixinTextLink("a.MoreWrap, .MoreWrap a, .MorePager a, .more.More, .MoreWrap a.more.More");
    mixinTextLink(`body.Section-BestOf .Tile .Message a`);
    mixinTextLink(
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
    mixinTextLink(`
        .Container .userContent a,
        .Container .UserContent a
    `);
    mixinTextLink(".BreadcrumbsBox .Breadcrumbs a", {
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
    mixinTextLinkNoDefaultLinkAppearance(`.Content .MessageList .ItemComment .InlineTags a`);
    mixinTextLinkNoDefaultLinkAppearance(`.Content  .MessageList .ItemDiscussion .InlineTags a`);
};

// Mixins replacement
export const mixinTextLink = (selector: string, overwrite?: {}) => {
    selector = trimTrailingCommas(selector);
    const selectors = selector.split(",");
    const linkColors = setAllLinkColors(overwrite);
    if (!selectors) {
        cssOut(selector, {
            color: colorOut(linkColors.color),
        });
        nestedWorkaround(trimTrailingCommas(selector), linkColors.nested);
    } else {
        selectors.map(s => {
            cssOut(selector, {
                color: colorOut(linkColors.color),
            });
            nestedWorkaround(trimTrailingCommas(s), linkColors.nested);
        });
    }
};

export const mixinTextLinkNoDefaultLinkAppearance = selector => {
    const globalVars = globalVariables();
    mixinTextLink(selector, { default: globalVars.mainColors.fg, textDecoration: "none" });
};
