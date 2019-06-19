/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { important, px } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export function srOnly() {
    return {
        position: important("absolute"),
        display: important("block"),
        width: important(px(1).toString()),
        height: important(px(1).toString()),
        padding: important(px(0).toString()),
        margin: important(px(-1).toString()),
        overflow: important("hidden"),
        clip: important(`rect(0, 0, 0, 0)`),
        border: important(px(0).toString()),
    };
}

export const visibility = useThemeCache(() => {
    const style = styleFactory("visibility");
    const onEmpty = (nest?: object) => {
        return style("onEmpty", {
            $nest: {
                "&:empty": {
                    display: "none",
                },
                ...nest,
            },
        });
    };

    const displayNone = style("displayNone", {
        display: important("none"),
    });

    return {
        onEmpty,
        displayNone,
    };
});
