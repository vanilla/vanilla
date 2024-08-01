/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important } from "csx";
export type areaHiddenType = "true" | "false" | boolean | undefined;

export const visibility = useThemeCache(() => {
    const onEmpty = (nest?: object) => {
        return css({
            "&:empty": {
                display: "none",
            },
            ...nest,
        });
    };

    const visuallyHidden = css(Mixins.absolute.srOnly());

    const displayNone = css({
        display: important("none"),
    });

    return {
        onEmpty,
        displayNone,
        visuallyHidden,
    };
});
