/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { allLinkStates, extendItemContainer, negative } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important } from "csx";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { metaContainerStyles } from "@vanilla/library/src/scripts/styles/metasStyles";
import trim from "validator/lib/trim";
import { logDebugConditionnal } from "@vanilla/utils";
import { CSSObject } from "@emotion/css";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import { Mixins } from "@library/styles/Mixins";

export const mixinMetaContainer = (selector: string, overwrites = {}) => {
    cssOut(selector, metaContainerStyles(overwrites));
};

export const linkSelectors = `
    .MessageList .ItemDiscussion .MItem.RoleTracker a:not(.Tag),
    .MessageList .ItemComment .MItem.RoleTracker a:not(.Tag),
    .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a:not(.Tag),
    .MainContent.Content .MItem.RoleTracker a:not(.Tag),
    .MessageList .ItemComment .Username,
    .MessageList .ItemDiscussion .Username,
    .AuthorInfo .MItem.RoleTracker a:not(.Tag),
    .MItem > a:not(.Tag),
`;

export const forumMetaCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;

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
            color: ColorsUtils.colorOut(globalVars.meta.text.color),
            fontSize: styleUnit(globalVars.meta.text.size),
        },
    );

    cssOut(`.MessageList .ItemComment span.MItem.RoleTracker`, {
        padding: 0,
        margin: 0,
    });

    // Links
    cssOut(trimTrailingCommas(linkSelectors).trim(), {
        display: "inline-flex",
        alignItems: "center",
        opacity: important(1),
        textDecoration: "none",
    });

    // Split because it seems there's a bug with TypeStyles and it's unable to handle the deep nesting.
    trimTrailingCommas(linkSelectors)
        .split(",")
        .map((s) => {
            const selector = trim(s);
            const debug = false;
            const linkStates: CSSObject = allLinkStates(
                {
                    noState: {
                        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                    },
                    hover: {
                        color: ColorsUtils.colorOut(globalVars.links.colors.hover),
                        textDecoration: "underline",
                    },
                    focus: {
                        color: ColorsUtils.colorOut(globalVars.links.colors.focus),
                        textDecoration: "underline",
                    },
                    keyboardFocus: {
                        color: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
                        textDecoration: "underline",
                    },
                    active: {
                        color: ColorsUtils.colorOut(globalVars.links.colors.active),
                        textDecoration: "underline",
                    },
                },
                {},
            );

            logDebugConditionnal(debug, linkStates);
            cssOut(selector, linkStates);
        });

    cssOut(
        `
        .MessageList .ItemDiscussion .MItem.RoleTracker a:not(.Tag),
        .MessageList .ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MessageList.Discussion .Item.ItemComment .MItem.RoleTracker a:not(.Tag),
        .MainContent.Content .MItem.RoleTracker a:not(.Tag),
    `,
        {
            ...Mixins.padding({
                horizontal: 0,
            }),
            ...Mixins.margin({
                all: 0,
            }),
        },
    );

    cssOut(
        `
        .Content .MessageList .RoleTracker > .Tag,
        .Tag
        `,
        {
            color: ColorsUtils.colorOut(globalVars.meta.text.color),
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
            marginLeft: styleUnit(globalVars.meta.spacing.default),
        },
    );

    cssOut(
        `
        .Meta-Discussion > .Tag,
        .idea-counter-module`,
        {
            marginRight: styleUnit(globalVars.meta.spacing.default),
        },
    );

    cssOut(`.Tag`, {
        background: "none",
    });

    cssOut(".AuthorWrap", {
        ...extendItemContainer(globalVars.meta.spacing.default),
    });

    cssOut(
        `
    .AuthorWrap a.Username,
    .AuthorWrap .${userCardClasses().link},
    .AuthorInfo .MItem,
    .DiscussionMeta .MItem,
    `,
        {
            ...Mixins.padding({
                all: 0,
            }),
            ...Mixins.margin({
                horizontal: styleUnit(globalVars.meta.spacing.default),
            }),
        },
    );

    mixinMetaContainer(`.Container .DataTable .DiscussionName .Meta.Meta-Discussion`, {
        overflow: "visible",
        display: "block",
    });

    cssOut(`.Container .AuthorInfo .MItem`, {
        display: "inline-flex",
        alignItems: "center",
    });

    cssOut(`.Container .AuthorInfo .MItem img`, {
        paddingLeft: styleUnit(12),
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

    cssOut(`.MItem img`, {
        width: "auto",
        height: styleUnit(12),
        ...Mixins.padding({
            left: 12,
        }),
    });

    cssOut(`.DataList.Discussions .ItemContent .Meta`, {
        marginLeft: styleUnit(negative(globalVars.meta.spacing.horizontalMargin)),
    });

    const linkColors = Mixins.clickable.itemState();
    const inlineTagSelector = `.InlineTags.Meta a`;
    cssOut(inlineTagSelector, {
        ...linkColors,
    });
};

function mixinMetaLinkContainer(selector: string) {
    selector = trimTrailingCommas(selector);
    const vars = globalVariables();
    const metaFg = ColorsUtils.colorOut(vars.meta.colors.fg);

    cssOut(selector, {
        color: metaFg,
        textDecoration: "none",
        ...{
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
