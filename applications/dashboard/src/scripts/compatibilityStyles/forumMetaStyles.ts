/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { allLinkStates, colorOut, margins, negative, paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important } from "csx";
import { cssOut, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { metaContainerStyles } from "@vanilla/library/src/scripts/styles/metasStyles";
import trim from "validator/lib/trim";

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
        .ItemDiscussion .Meta,
        .DataList .Meta
    `,
        {
            color: colorOut(globalVars.meta.colors.fg),
            fontSize: unit(globalVars.meta.text.fontSize),
        },
    );

    const linkSelectors = `
        .MessageList .ItemDiscussion .MItem.RoleTracker a:not(.Tag),
        .MessageList .ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MItem.RoleTracker a:not(.Tag),
        .MessageList .ItemComment .Username,
        .MessageList .ItemDiscussion .Username,
        .AuthorInfo .MItem.RoleTracker a:not(.Tag),
        .MItem > a:not(.Tag),
        `;

    cssOut(`.MessageList .ItemComment span.MItem.RoleTracker`, {
        padding: 0,
        margin: 0,
    });

    // Links
    cssOut(linkSelectors, {
        display: "inline-flex",
        alignItems: "center",
        opacity: important(1),
        textDecoration: "none",
    });

    // Split because it seems there's a bug with TypeStyles and it's unable to handle the deep nesting.
    trimTrailingCommas(linkSelectors)
        .split(",")
        .map(s => {
            cssOut(trim(s), {
                ...allLinkStates({
                    noState: {
                        color: colorOut(globalVars.mainColors.fg),
                    },
                    hover: {
                        color: colorOut(globalVars.links.colors.hover),
                        textDecoration: "underline",
                    },
                    focus: {
                        color: colorOut(globalVars.links.colors.focus),
                        textDecoration: "underline",
                    },
                    keyboardFocus: {
                        color: colorOut(globalVars.links.colors.keyboardFocus),
                        textDecoration: "underline",
                    },
                    active: {
                        color: colorOut(globalVars.links.colors.active),
                        textDecoration: "underline",
                    },
                }),
            });
        });

    cssOut(
        `
        .MessageList .ItemDiscussion .MItem.RoleTracker a:not(.Tag),
        .MessageList .ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MItem.RoleTracker a:not(.Tag),
    `,
        {
            ...paddings({
                horizontal: 0,
            }),
            ...margins({
                all: 0,
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
        .Content .MessageList .RoleTracker > .Tag,
        .Tag
        `,
        {
            color: colorOut(globalVars.mainColors.fg),
        },
    );

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

    cssOut(`.Tag`, {
        background: "none",
    });

    cssOut(
        `
    .AuthorWrap a.Username,
    .AuthorInfo .MItem,
    .DiscussionMeta .MItem,
    `,
        {
            ...paddings({
                all: 0,
            }),
            ...margins({
                horizontal: unit(globalVars.meta.spacing.default),
            }),
        },
    );

    mixinMetaContainer(`.Container .DataTable .DiscussionName .Meta.Meta-Discussion`, {
        overflow: "visible",
    });

    cssOut(`.Container .AuthorInfo .MItem`, {
        display: "inline-flex",
        alignItems: "center",
    });

    cssOut(`.Container .AuthorInfo .MItem img`, {
        paddingLeft: unit(12),
    });

    cssOut(
        `
        .DataList .MItem > a:hover,
        .DataList .MItem > a:focus,
        .DataList .MItem > a:active,
        .DataList .MItem > a.focus-visible
        `,
        {
            textDecoration: "none",
        },
    );

    cssOut(
        `
    .Content  .idea-counter-module
    `,
        {
            padding: 0,
        },
    );

    cssOut(`.MItem img`, {
        width: "auto",
        height: unit(12),
        ...paddings({
            left: 12,
        }),
    });

    cssOut(`.DataList.Discussions .ItemContent .Meta`, {
        marginLeft: unit(negative(globalVars.meta.text.margin)),
    });
};

function mixinMetaLinkContainer(selector: string) {
    selector = trimTrailingCommas(selector);
    const vars = globalVariables();
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
