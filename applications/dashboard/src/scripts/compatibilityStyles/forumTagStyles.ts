/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    borders,
    paddings,
    fonts,
    colorOut,
    defaultTransition,
    margins,
    userSelect,
    negative,
} from "@library/styles/styleHelpers";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
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
            ...margins({
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
    const globalVars = globalVariables();

    selectors.map(s => {
        cssOut(selector, {
            color: colorOut(vars.font.color),
            maxWidth: percent(100),
            display: "inline-block",
            whiteSpace: "normal",
            textDecoration: important("none"),
            textOverflow: "ellipsis",
            ...userSelect(),
            ...paddings(vars.padding),
            ...borders(vars.border),
            ...fonts(vars.font),
            ...defaultTransition("border"),
            ...margins(vars.margin),
        });
        nestedWorkaround(trimTrailingCommas(s), vars.$nest);
    });
}
