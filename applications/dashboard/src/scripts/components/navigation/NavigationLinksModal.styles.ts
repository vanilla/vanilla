/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const navigationLinksModalClasses = () => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const modalButton = css({
        marginLeft: 24,
    });
    const modalDescription = css({
        ...Mixins.margin({
            vertical: 20,
        }),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
            weight: metasVars.font.weight,
            color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.7)),
        }),
        ...{
            "a[href]": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium"),
                    color: globalVars.mainColors.primary,
                    lineHeight: metasVars.font.lineHeight,
                }),
            },
            "a[href]:hover": {
                textDecoration: "underline",
            },
        },
    });

    return {
        modalButton,
        modalDescription,
    };
};
