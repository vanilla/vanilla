/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";

interface IFollowClassParams {
    isOpen: boolean;
    isFollowed: boolean;
}

export const categoryFollowDropDownClasses = useThemeCache((params: IFollowClassParams) => {
    const { isOpen, isFollowed } = params;
    const globalVars = globalVariables();

    const layout = css({
        marginLeft: "auto",
        marginBottom: globalVars.spacer.size,
    });

    const followButton = css({
        aspectRatio: "1/1",
        borderRadius: globalVars.border.radius,
        "&&": {
            backgroundColor: isOpen ? ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)) : "transparent",
            color: isFollowed || isOpen ? ColorsUtils.colorOut(globalVars.mainColors.primary) : "inherit",
            padding: 4,
            "&:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const preferencesButton = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    const heading = css({
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const checkBox = css({
        paddingBottom: 4,
    });

    const fullWidth = css({
        width: "100%",
    });

    return {
        layout,
        followButton,
        preferencesButton,
        heading,
        checkBox,
        fullWidth,
    };
});
