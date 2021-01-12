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
import { tagVariables } from "@library/styles/tagStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important, percent } from "csx";

export const forumTagCSS = () => {
    const vars = tagVariables();

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

    cssOut(".Panel.Panel li.TagCloud-Item", {
        margin: 0,
        padding: 0,
        maxWidth: percent(100),
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
