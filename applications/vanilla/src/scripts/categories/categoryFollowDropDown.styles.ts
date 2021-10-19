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

    const groupLayout = css({
        margin: 0,
        ...Mixins.padding({
            top: 17,
            bottom: 20,
            horizontal: 16,
        }),
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

    const radioItem = css({
        alignItems: "flex-start",
        ...Mixins.padding({
            vertical: 0,
            horizontal: 0,
        }),
        "&:not(:last-of-type)": {
            ...Mixins.padding({
                bottom: 18,
            }),
        },
        "& > span:first-of-type": {
            marginTop: 2,
        },
    });

    const heading = css({
        fontSize: globalVars.fonts.size.large,
        ...Mixins.padding({
            vertical: 12,
            horizontal: 16,
        }),
        borderBottom: singleBorder(),
    });

    return {
        layout,
        groupLayout,
        followButton,
        radioItem,
        heading,
    };
});

export const radioLabelClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const layout = css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "flex-start",
        paddingLeft: 8,
    });

    const title = css({
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        lineHeight: globalVars.lineHeights.condensed,
        marginBottom: 7,
    });

    const description = css({});

    return {
        layout,
        title,
        description,
    };
});
