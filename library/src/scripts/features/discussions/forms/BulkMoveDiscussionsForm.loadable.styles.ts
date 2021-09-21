/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const bulkMoveDiscussionFormClasses = () => {
    const globalVars = globalVariables();

    const separatedSection = css({
        ...Mixins.margin({
            horizontal: -16,
            top: 16,
        }),
        ...Mixins.padding({
            horizontal: 16,
            top: 20,
            bottom: 4, // FrameBody has 16px bottom padding
        }),
        borderTop: singleBorder({}),
    });

    const checkboxLabel = css({
        "& span": {
            fontWeight: globalVars.fonts.weights.normal,
        },
    });

    const errorMessageOffset = css({
        marginBottom: globalVariables().spacer.size,
    });

    return {
        separatedSection,
        checkboxLabel,
        errorMessageOffset,
    };
};
