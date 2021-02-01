/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { defaultTransition, userSelect, negative } from "@library/styles/styleHelpers";
import { Mixins } from "@library/styles/Mixins";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { tagVariables, TagType } from "@library/styles/tagStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important, percent } from "csx";

export const forumTagCSS = () => {
    const vars = tagVariables();
    const tagItemWidth = vars.tagItem.type === TagType.LIST ? "100%" : "auto";
    const tagItemListStyle = vars.tagItem.type === TagType.LIST ? Mixins.flex.spaceBetween() : {};

    cssOut(
        `
        .TagCloud
    `,
        {
            ...Mixins.margin({
                vertical: negative(vars.margin.vertical),
                horizontal: negative(vars.margin.horizontal),
            }),
            padding: 0,
        },
    );

    cssOut(".Panel.Panel li.TagCloud-Item.TagCloud-Item", {
        padding: 0,
        width: tagItemWidth,
        maxWidth: percent(100),
        ...Mixins.margin(vars.tagItem.margin),
        "& a": {
            ...tagItemListStyle,
            ...Mixins.font(vars.tagItem.font),
            ...Mixins.background(vars.tagItem.background),
        },
        "&  a:hover, a:active, a:focus": {
            ...Mixins.font(vars.tagItem.fontState),
            ...Mixins.background(vars.tagItem.backgroundState),
        },
    });

    cssOut(`.AuthorInfo .MItem.RoleTracker a`, {
        textDecoration: important("none"),
    });

    mixinTag(`.TagCloud a`);
    mixinTag(`.Tag`);
    mixinTag(`.DataTableWrap a.Tag`);
    mixinTag(`.Container .MessageList .ItemComment .MItem.RoleTracker a.Tag`);
    mixinTag(
        `
        .MessageList .ItemComment .MItem.RoleTracker a.Tag,
        .MessageList .ItemDiscussion .MItem.RoleTracker a.Tag
        `,
    );
};

function mixinTag(selector: string, overwrite?: {}) {
    selector = trimTrailingCommas(selector);
    const selectors = selector.split(",") || [];

    if (selectors.length === 0) {
        selectors.push(selector);
    }
    const vars = tagVariables();
    selectors.map((s) => {
        cssOut(selector, {
            maxWidth: percent(100),
            display: "inline-block",
            whiteSpace: "normal",
            textDecoration: important("none"),
            textOverflow: "ellipsis",
            ...userSelect(),
            ...Mixins.padding(vars.padding),
            ...Mixins.border(vars.border),
            ...Mixins.font(vars.font),
            ...defaultTransition("border"),
            ...Mixins.margin(vars.margin),
            ...vars.nested,
        });
    });
}
