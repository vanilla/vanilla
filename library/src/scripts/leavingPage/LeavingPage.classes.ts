/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { navLinksVariables } from "@library/navigation/navLinksStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

/**
 * Classes for the leaving page.
 */
export const leavingPageClasses = useThemeCache(() => {
    const linkColors = Mixins.clickable.itemState();

    const container = css({
        margin: "auto",
        marginTop: 32,
        maxWidth: 798,
    });

    const backLink = css({
        ...Mixins.font({
            weight: 600,
        }),
        ...linkColors,
    });

    const contentContainer = css({
        display: "flex",
        justifyContent: "center",
        maxWidth: 632,
        margin: "auto",
        marginTop: 32,
    });

    const content = css({
        "& h2": {
            marginBottom: 16,
            ...Mixins.font({
                ...globalVariables().fontSizeAndWeightVars("largeTitle", "bold"),
            }),
        },
    });

    const description = css({
        marginBottom: 24,
        ...Mixins.font({
            size: 18,
        }),
        "& span": {
            ...Mixins.font({
                weight: 700,
            }),
        },
    });

    return {
        container,
        backLink,
        contentContainer,
        content,
        description,
    };
});
