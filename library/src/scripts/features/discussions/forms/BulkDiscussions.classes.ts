/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";

export const bulkDiscussionsClasses = useThemeCache(() => {
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

    const errorLine = css({
        display: "flex",
        alignItems: "center",
    });

    const errorLabel = css({
        marginRight: 8,
    });

    const autocomplete = css({
        width: "100%",
    });

    return {
        autocomplete,
        separatedSection,
        checkboxLabel,
        errorMessageOffset,
        errorLine,
        errorLabel,
    };
});
