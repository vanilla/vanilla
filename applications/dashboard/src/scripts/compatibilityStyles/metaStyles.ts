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

    mixinMetaLinkContainer(".DataList");
    mixinMetaLinkContainer(".DataList-Search");
    mixinMetaLinkContainer(".Breadcrumbs");
    mixinMetaLinkContainer(".DataList");
    mixinMetaLinkContainer(".MessageList");
    mixinMetaLinkContainer(".MessageList");
    mixinMetaLinkContainer(".DataTableWrap");
    // mixinMetaLinkContainer(".Meta.Meta-Discussion");

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
        `,
        {
            color: primary,
            borderColor: primary,
            textDecoration: "none",
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
        `,
        {
            textDecoration: important("none"),
            ...margins({
                all: globalVars.meta.spacing.default,
            }),
        },
    );

    cssOut(".MItem .Tag", {
        ...margins({ all: important(0) }),
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
