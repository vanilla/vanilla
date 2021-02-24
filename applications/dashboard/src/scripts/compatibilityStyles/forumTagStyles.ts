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
import { tagVariables, TagType } from "@library/metas/Tag.variables";
import { important, percent } from "csx";
import { CSSObject, injectGlobal } from "@emotion/css";

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

    cssOut("li.TagCloud-Item.TagCloud-Item.TagCloud-Item", {
        padding: 0,
        margin: 0,
        width: tagItemWidth,
        maxWidth: percent(100),
        "& a": {
            ...tagItemListStyle,
            ...Mixins.margin(vars.tagItem.margin),
            ...Mixins.font(vars.tagItem.font),
            ...Mixins.background(vars.tagItem.background),
            ...Mixins.border(vars.tagItem.border),
        },
        "& a:hover, a:active, a:focus": {
            ...Mixins.font(vars.tagItem.fontState),
            ...Mixins.background(vars.tagItem.backgroundState),
            ...Mixins.border(vars.tagItem.borderState),
        },
    });

    cssOut(`.AuthorInfo .MItem.RoleTracker a`, {
        textDecoration: important("none"),
    });

    injectGlobal({
        [`.TagCloud a,
        .Container .MessageList .ItemComment .MItem.RoleTracker a.Tag,
        .MessageList .ItemComment .MItem.RoleTracker a.Tag,
        .MessageList .ItemDiscussion .MItem.RoleTracker a.Tag`]: tagLinkStyle(),
    });
};

export function tagStyle(): CSSObject {
    const vars = tagVariables();

    return {
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
    };
}
export function tagLinkStyle(): CSSObject {
    const vars = tagVariables();

    return {
        ...tagStyle(),
        borderColor: vars.nested.color,
        ...vars.nested,
    };
}
