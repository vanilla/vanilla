/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleUnit } from "@library/styles/styleUnit";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { metaLinkItemStyle } from "@library/metas/Metas.styles";
import { Mixins } from "@library/styles/Mixins";

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
