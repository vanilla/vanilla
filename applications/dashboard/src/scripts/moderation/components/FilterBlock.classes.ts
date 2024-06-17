/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";

export const filterBlockClasses = () => {
    const root = css({
        ...Mixins.margin({
            bottom: globalVariables().spacer.componentInner,
        }),
        "&:not(:first-child)": {
            ...Mixins.margin({
                top: globalVariables().spacer.panelComponent * 1.5,
            }),
        },
    });
    const title = css({
        display: "block",
        fontSize: globalVariables().fonts.size.medium,
        fontWeight: globalVariables().fonts.weights.semiBold,
    });
    const checkbox = css({
        padding: 0,
        margin: "8px 0 0 0!important",
    });
    const spacingContainer = css({
        ...Mixins.margin({
            vertical: globalVariables().spacer.componentInner,
        }),
    });
    const dynamicInput = css({
        ...Mixins.margin({
            top: 9,
        }),
    });
    const dynamicFilterButton = css({
        fontWeight: globalVariables().fonts.weights.normal,
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
        display: "flex",
        alignItems: "center",
        lineHeight: 1,
        paddingBottom: 9,
        ...Mixins.margin({
            top: 14,
        }),
        "& svg": {
            ...Mixins.margin({ right: 4 }),
            width: "1.25em",
        },
    });
    return { root, title, checkbox, spacingContainer, dynamicInput, dynamicFilterButton };
};
