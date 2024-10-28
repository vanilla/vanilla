/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";

export const suggestedContentClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const layout = css({
        ...Mixins.margin({ vertical: globalVars.spacer.componentInner }),
    });

    const categoryFollowButtonLayout = css({
        display: "flex",
        alignItems: "start",
        justifyContent: "start",
        gap: globalVars.spacer.componentInner / 2,
        flexWrap: "wrap",
        "& > div": {
            margin: 0,
        },
    });

    const headerAlignment = css({
        alignItems: "center",
    });

    const refetchButton = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    return { layout, categoryFollowButtonLayout, headerAlignment, refetchButton };
});
