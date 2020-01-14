/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRaw } from "typestyle";
import {
    borders,
    colorOut,
    margins,
    negative,
    pointerEvents,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const metaCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);
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
        .MessageList .ItemDiscussion .Username
        `,
        {
            color: primary,
            borderColor: primary,
            textDecoration: "none",
        },
    );
    mixinMetaLinkContainer(".DataList .Meta");
    mixinMetaLinkContainer(".MessageList .Meta");
    mixinMetaLinkContainer(".DataList .AuthorInfo");
    mixinMetaLinkContainer(".MessageList .AuthorInfo");
    mixinMetaLinkContainer(".DataList-Search .MItem-Author");
    mixinMetaLinkContainer(".DataList .Excerpt");
    mixinMetaLinkContainer(".DataList .CategoryDescription");
    mixinMetaLinkContainer(".MessageList .Excerpt");
    mixinMetaLinkContainer(".MessageList .CategoryDescription");
    mixinMetaLinkContainer(".Breadcrumbs");
    mixinMetaLinkContainer(".DataList .Tag");
    mixinMetaLinkContainer(".DataList .Tag-Poll");
    mixinMetaLinkContainer(".DataList .RoleTracker");
    mixinMetaLinkContainer(".DataList .IdeationTag");
    mixinMetaLinkContainer(".MessageList .Tag");
    mixinMetaLinkContainer(".MessageList .Tag-Poll");
    mixinMetaLinkContainer(".MessageList .RoleTracker");
    mixinMetaLinkContainer(".MessageList .IdeationTag");
    mixinMetaLinkContainer(".DataTableWrap .Tag");
    mixinMetaLinkContainer(".DataTableWrap .Tag-Poll");
    mixinMetaLinkContainer(".DataTableWrap .RoleTracker");
    mixinMetaLinkContainer(".DataTableWrap .IdeationTag");
    mixinMetaLinkContainer(".MessageList .ItemComment .Username");
    mixinMetaLinkContainer(".MessageList .ItemDiscussion .Username");

    cssOut(".Meta-Discussion .Tag", {
        ...margins({
            horizontal: 3,
        }),
    });

    cssOut(".Meta-Discussion > .Tag", {
        marginLeft: unit(6),
    });

    cssOut(selector, {
        color: metaFg,
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
