/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { css } from "@emotion/css";

export const profileAnalyticsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const link = css({
        "&&": {
            display: "flex",
            width: "100%",
            justifyContent: "flex-end",
            marginTop: globalVars.gutter.half / 2,
            "&& a": {
                ...Mixins.font({
                    color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                    ...globalVars.fontSizeAndWeightVars("small", "normal"),
                }),
                ...Mixins.flex.middleLeft(),
            },
        },
    });

    return {
        link,
    };
});
