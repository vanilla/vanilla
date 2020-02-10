/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    allLinkStates,
    borders,
    colorOut,
    importantColorOut,
    margins,
    paddings,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important } from "csx";
import { cssOut, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { metaContainerStyles } from "@vanilla/library/src/scripts/styles/metasStyles";

export const mixinMetaContainer = (selector: string, overwrites = {}) => {
    cssOut(selector, metaContainerStyles({ flexContents: true, ...overwrites }));
};

export const forumMetaCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);

    mixinMetaLinkContainer(".DataList");
    mixinMetaLinkContainer(".DataList-Search");
    mixinMetaLinkContainer(".Breadcrumbs");
    mixinMetaLinkContainer(".DataList");
    mixinMetaLinkContainer(".MessageList");
    mixinMetaLinkContainer(".MessageList");
    mixinMetaLinkContainer(".DataTableWrap");
    mixinMetaLinkContainer(`.Container .Frame-contentWrap .ChildCategories`);
    mixinMetaLinkContainer(`.Item.Application .Meta`);
    mixinMetaLinkContainer(`.Meta.Group-Meta.Group-Info`);

    cssOut(
        `
        .DataList .Meta .Tag-Announcement,
        .DataList .NewCommentCount,
        .DataList .HasNew.HasNew,
        .MessageList .Tag-Announcement,
        .MessageList .NewCommentCount,
        .MessageList .HasNew.HasNew,
        .DataTableWrap .Tag-Announcement,
        .DataTableWrap .NewCommentCount,
        .DataTableWrap .HasNew.HasNew,
        .MessageList .ItemComment .Username,
        .MessageList .ItemDiscussion .Username,
        a.Tag
        `,
        {
            color: importantColorOut(fg),
            ...borders(),
            textDecoration: "none",
            ...allLinkStates({
                allStates: {
                    borderColor: colorOut(globalVars.mainColors.fg),
                },
            }),
        },
    );

    cssOut(
        `
        .MessageList .ItemDiscussion a.Username,
        .MessageList .ItemDiscussion .Username,
        .MessageList .ItemDiscussion .MItem.RoleTracker a,
        .MessageList .ItemComment .MItem.RoleTracker a,
        .MessageList .ItemComment a.Username,
        .MessageList .ItemComment .Username,
        .MainContent.Content .MessageList.Discussion .Item.ItemComment a.Username,
        .MainContent.Content .MessageList.Discussion .Item.ItemDiscussion a.Username,
        .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a,
        `,
        {
            color: colorOut(globalVars.mainColors.fg),
            opacity: important(1),
            ...allLinkStates({
                hover: {
                    color: colorOut(globalVars.links.colors.hover),
                },
                focus: {
                    color: colorOut(globalVars.links.colors.focus),
                },
                active: {
                    color: colorOut(globalVars.links.colors.active),
                },
            }),
        },
    );

    cssOut(
        `
        .Container .Frame-contentWrap .ChildCategories,
        .Container .Frame-contentWrap .ChildCategories a,
        .DiscussionName .Wrap > a,
        .Gloss
        `,
        {
            fontSize: unit(globalVars.meta.text.fontSize),
            color: colorOut(globalVars.meta.text.color),
        },
    );

    cssOut(
        `
        .Meta-Discussion .Tag,
        .DataList .Author .Username,
        .DataList .MItem,
        .DataList .MItem.Category,
        .DataList .ChildCategories,
        .MessageList .Author .Username,
        .MessageList .MItem,
        .MessageList .MItem.Category,
        .MessageList .ChildCategories
        .Container .Frame-contentWrap .ChildCategories > b,
        .Container .Frame-contentWrap .ChildCategories a,
        .Groups .DataTable .MItem a,
        .DataTable .MItem a,
        .Container .DataTable .MItem.Category,
        .DiscussionHeader .AuthorWrap .Username,
        .Content .MessageList .Tag,
        .DataList.DataList-Search .CrumbLabel,
        .Item.Application .MItem
        `,
        {
            textDecoration: important("none"),
            color: colorOut(globalVars.mainColors.fg),
            ...paddings({
                horizontal: 3,
            }),
            ...margins({
                all: globalVars.meta.spacing.default,
            }),
            ...borders(),
        },
    );

    cssOut(`.Content .MessageList .RoleTracker > .Tag`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(".MItem a.Tag", {
        ...margins({ all: important(0) }),
        border: important(0),
        padding: important(0),
    });

    cssOut(
        `
        .ItemDiscussion.ItemIdea .Title a,
        .Content .DataList .Tag,
        .Content .DataList .Tag-Poll,
        .Content .DataList .RoleTracker,
        .Content .DataList .IdeationTag,
        .Content .MessageList .Tag,
        .Content .MessageList .Tag-Poll,
        .Content .MessageList .RoleTracker,
        .Content .MessageList .IdeationTag,
        .Content .DataTableWrap .Tag,
        .Content .DataTableWrap .Tag-Poll,
        .Content .DataTableWrap .RoleTracker,
        .Content .DataTableWrap .IdeationTag
        `,
        {
            marginLeft: unit(globalVars.meta.spacing.default),
        },
    );

    cssOut(
        `
        .Meta-Discussion > .Tag,
        .idea-counter-module`,
        {
            marginRight: unit(globalVars.meta.spacing.default),
        },
    );

    mixinMetaContainer(`.Container .DataTable .DiscussionName .Meta.Meta-Discussion`, {
        overflow: "visible",
    });

    cssOut(
        `
        .MainContent.Content .MessageList.Discussion .Item.ItemComment a.Username,
        .MainContent.Content .MessageList.Discussion .Item.ItemDiscussion a.Username,
        .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a`,
        {
            opacity: important(1),
        },
    );

    cssOut(
        `
        .MessageList .ItemComment .MItem.RoleTracker a,
        .MessageList .ItemDiscussion .MItem.RoleTracker a
        `,
        {
            textDecoration: "none",
        },
    );

    cssOut(
        `
        .MItem.RoleTracker,
        .MItem.Rank,
        .AuthorInfo .MItem.RoleTitle,
        .MessageList .ItemComment .MItem.RoleTitle
        `,
        {
            ...borders(),
        },
    );
};

function mixinMetaLinkContainer(selector: string) {
    selector = trimTrailingCommas(selector);
    const vars = globalVariables();
    const formVars = formElementsVariables();
    const mainColors = vars.mainColors;

    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(vars.meta.colors.fg);

    cssOut(selector, {
        color: metaFg,
        textDecoration: "none",
        $nest: {
            "& a": {
                color: metaFg,
                fontSize: "inherit",
                textDecoration: "underline",
            },
            "& a:hover": {
                textDecoration: "underline",
            },
            "& a:focus": {
                textDecoration: "underline",
            },
            "& a.focus-visible": {
                textDecoration: "underline",
            },
        },
    });
}
