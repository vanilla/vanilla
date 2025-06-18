/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";

const aiSearchSummaryClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const labelContainer = css({
        display: "inline-flex",
        alignItems: "center",
        marginBottom: "6px",
    });

    const label = css({
        marginLeft: "6px",
    });

    const resultsContainer = css({
        border: singleBorder(),
        padding: "1em",

        "& a[href]": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                color: globalVars.mainColors.primary,
                lineHeight: metasVars.font.lineHeight,
            }),
        },

        "& a[href]:hover": {
            textDecoration: "underline",
        },
    });

    const footer = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "flex-end",
        marginTop: "1em",
    });

    const iconInButton = css({
        marginRight: 5,
    });

    return {
        labelContainer,
        label,
        resultsContainer,
        footer,
        iconInButton,
    };
});

export default aiSearchSummaryClasses;
