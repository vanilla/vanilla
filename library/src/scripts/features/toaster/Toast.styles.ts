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
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        width: 280,
        maxHeight: 120,
        overflowX: "hidden",
        overflowY: "auto",
        "&:not(last-of-type)": {
            marginTop: styleUnit(globalVars.gutter.size),
        },
        p: {
            margin: 0,
        },
    });

    const closeButton = css({
        position: "absolute",
        top: 16,
        right: 0,
    });

    return { root, closeButton };
};
