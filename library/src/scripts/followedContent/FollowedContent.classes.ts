/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const followedContentClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({});

    const section = css({
        marginTop: globalVars.spacer.headingItem,
    });

    const subtitle = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("subTitle"),
        }),
        ...Mixins.margin({ top: globalVars.spacer.mainLayout, bottom: globalVars.spacer.pageComponentCompact }),
    });

    const sortBy = css({
        display: "flex",
        justifyContent: "flex-start",
        ...Mixins.margin({ bottom: globalVars.spacer.headingBoxCompact }),
    });

    const photoWrap = css({
        height: globalVars.spacer.size * 2,
        width: globalVars.spacer.size * 2,
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        overflow: "hidden",
        borderRadius: "50%",
        marginRight: 12,

        "& img": {
            width: "100%",
            height: "auto",
        },
    });

    const iconWrap = css({
        marginRight: 0,
    });

    const name = css({
        float: "left",
    });

    return {
        root,
        section,
        subtitle,
        sortBy,
        photoWrap,
        iconWrap,
        name,
    };
});
