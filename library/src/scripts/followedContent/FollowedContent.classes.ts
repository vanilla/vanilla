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

    const tabsContent = css({
        paddingTop: 16,
    });

    const subtitle = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("subTitle"),
        }),
        ...Mixins.margin({ top: globalVars.spacer.mainLayout, bottom: globalVars.spacer.pageComponentCompact }),
    });

    const sortByAndPager = css({
        display: "flex",
        justifyContent: "space-between",
        ...Mixins.margin({ bottom: globalVars.spacer.headingBoxCompact }),
    });

    const pager = css({
        paddingBottom: 0,
        paddingTop: 0,
        marginTop: -8,
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
            display: "block",
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
        tabsContent,
        subtitle,
        sortByAndPager,
        pager,
        photoWrap,
        iconWrap,
        name,
    };
});
