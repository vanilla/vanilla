/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleUnit } from "@library/styles/styleUnit";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { metaContainerStyle, metaItemStyle, metaLinkItemStyle } from "@library/metas/Metas.styles";
import { tagMixin } from "@library/metas/Tags.styles";
import { metasVariables } from "@library/metas/Metas.variables";
import { Mixins } from "@library/styles/Mixins";

import { tagPresetVariables, tagsVariables } from "@library/metas/Tags.variables";

export const metasCSS = () => {
    const metasVars = metasVariables();
    const presets = tagPresetVariables();

    cssOut(`.Meta.Meta, .AuthorInfo`, {
        ...metaContainerStyle(),
        "& > .MItem": {
            ...metaItemStyle(),

            "&.IdeationTag": {
                //This element contains a .Tag, so it shouldn't get extra padding
                ...Mixins.padding({
                    all: 0,
                }),
            },

            "&.BadgeDescription": {
                whiteSpace: "initial",
            },

            "&.Hidden, &.RSS, &.JustNew": {
                display: "none",
            },
        },
        "& .Tag": {
            ...tagMixin(tagsVariables(), presets.standard, false),
            ...Mixins.margin(metasVars.spacing),
            background: "none",
        },
        "& a.Tag": {
            ...tagMixin(tagsVariables(), presets.standard, true),
            ...Mixins.margin(metasVars.spacing),
        },

        "& .MItem > .Tag": {
            ...Mixins.margin({
                all: 0,
            }),
        },
        "& > .MItem-Resolved": {
            width: 13,
            height: 14,
            padding: 0,
            marginBottom: 0,
            verticalAlign: "middle",
        },
    });

    cssOut(`.Meta.Meta .MItem a`, {
        ...metaLinkItemStyle(),
        display: "inline",
    });

    cssOut(`.DataList-Notes .Meta.Meta`, {
        marginLeft: 0,
    });

    // FIXME: Once we resolve the absolute positioning in these cells
    // This won't be needed anymore.
    cssOut(".BlockColumn.BlockColumn", {
        "& .Meta > *": {
            marginLeft: 0,
            marginRight: (metasVars.spacing.horizontal! as number) * 2,
        },
    });

    // Special case for resolved
    cssOut(".resolved2-unresolved, .resolved2-resolved", {
        top: 0,
        display: "block",
    });

    // Special case for child categories in modern layout.
    // To see Modern Layout + "Discussions" type category with child categories.
    // Look at category list view.
    cssOut(".ChildCategories", {
        ...Mixins.margin({
            horizontal: metasVars.spacing.horizontal,
        }),
    });
};

function mixinMetaLinkContainer(selector: string): void {
    cssOut(selector, {
        a: {
            ...metaLinkItemStyle(),
            fontSize: "inherit",
            textDecoration: "underline",
        },
    });
}

export const forumMetaCSS = () => {
    mixinMetaLinkContainer(`.Container .Frame-contentWrap .ChildCategories`);

    cssOut(`.Tag`, {
        background: "none",
    });

    cssOut(`.MItem.RoleTracker`, {
        ...Mixins.margin({ all: 0 }),
        ...Mixins.padding({ all: 0 }),
        ...{
            "& a:not(.Tag)": {
                ...Mixins.margin({ all: 0 }),
                ...Mixins.padding({ all: 0 }),
            },
        },
    });

    const linkColors = Mixins.clickable.itemState();
    const inlineTagSelector = `.InlineTags.Meta a`;
    cssOut(inlineTagSelector, {
        ...linkColors,
    });

    // FIXME: Where is this?
    cssOut(`.MItem img`, {
        width: "auto",
        height: styleUnit(12),
        ...Mixins.padding({
            left: 12,
        }),
    });
};
