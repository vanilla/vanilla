/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";

export const createPostFormAssetClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formContainer = css({
        "& > div": {
            ...Mixins.margin({
                vertical: globalVars.spacer.componentInner,
            }),
        },
    });
    const categoryTypeContainer = css({
        display: "flex",
        flexWrap: "wrap",
        gap: 16,
        "& > div": {
            flexBasis: "100%",
            flex: 1,
        },
    });
    const main = css({
        display: "flex",
        flexDirection: "column",
        flexWrap: "wrap",
        gap: 16,
    });
    const postFieldsContainer = css({});
    const postBodyContainer = css({
        width: "100%",
    });
    const tagsContainer = css({
        "& li": {
            padding: 0,
            ...Mixins.margin({
                vertical: globalVars.spacer.componentInner,
            }),
        },
    });
    const labelStyle = css({
        display: "inline-block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
        }),
        marginBottom: 4,
    });
    const popularTagsLayout = css({});
    const announcementContainer = css({});

    return {
        formContainer,
        categoryTypeContainer,
        main,
        postFieldsContainer,
        postBodyContainer,
        tagsContainer,
        labelStyle,
        popularTagsLayout,
        announcementContainer,
    };
});
