/*
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { css } from "@emotion/css";
import { ColorVar } from "@library/styles/CssVar";

export const toastClasses = () => {
    const globalVars = globalVariables();
    const root = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
        }),
        zIndex: 1,
        ...Mixins.padding({
            horizontal: globalVars.gutter.size,
            vertical: globalVars.gutter.size,
        }),

        ...Mixins.border(),
        ...shadowHelper().dropDown(),
        background: ColorsUtils.varOverride(ColorVar.Background, globalVars.mainColors.bg),
        color: ColorsUtils.varOverride(ColorVar.Foreground, globalVars.mainColors.fg),
        width: 280,
        maxWidth: "100%",
        maxHeight: 120,
        overflowX: "hidden",
        overflowY: "auto",
        "&:not(last-of-type)": {
            marginTop: styleUnit(globalVars.gutter.size),
        },
        p: {
            margin: 0,
        },
        a: {
            ...Mixins.clickable.itemState(),
        },
    });

    const wide = css({
        width: 380,
    });

    const closeButton = css({
        position: "absolute",
        top: 16,
        right: 0,
    });

    return { root, closeButton, wide };
};
