/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { percent } from "csx";
import { css } from "@emotion/css";

export const statClasses = useThemeCache(() => {
    const mediaQueries = oneColumnVariables().mediaQueries();
    const globalVars = globalVariables();

    const title = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("subTitle", "bold"),
        }),
        ...Mixins.margin({
            bottom: globalVars.spacer.headingBox,
        }),
    });

    const container = css({
        ...Mixins.padding({
            vertical: globalVars.spacer.size,
        }),
        ...Mixins.flex.middle(),
        ...mediaQueries.oneColumnDown({
            flexWrap: "wrap",
            ...Mixins.padding({
                vertical: globalVars.spacer.headingItem,
            }),
        }),
    });

    const statItem = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        maxWidth: percent(50),
        ...Mixins.font({
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        }),
    });

    const statItemSkeleton = css({
        minWidth: 100,
    });

    const statItemLink = css({
        "&:hover, &:focus, &:active, &.focus-visible": {
            ...Mixins.font({
                color: globalVars.mainColors.primary,
            }),
        },
    });

    const statItemResponsive = css({
        flexGrow: 1,
        boxSizing: "content-box",
        ...Mixins.padding({
            horizontal: globalVars.spacer.size,
        }),
        "&:not(:last-of-type)": {
            borderRight: singleBorder(),
        },
        ...mediaQueries.oneColumnDown({
            flexGrow: 0,
            borderLeft: singleBorder(),
            ...Mixins.margin({
                vertical: globalVars.spacer.size / 2,
                left: -1,
            }),
            "&:last-child": {
                borderRight: singleBorder(),
            },
            "& > div": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("subTitle", "semiBold"),
                }),
            },
        }),
    });

    const statData = css({
        marginTop: styleUnit(3),
        ...Mixins.font({
            size: globalVars.fonts.size.medium * 2,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        whiteSpace: "nowrap",
    });

    const statLabel = css({
        marginTop: styleUnit(2),
        marginBottom: styleUnit(3),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
            lineHeight: globalVars.lineHeights.condensed,
        }),
        whiteSpace: "nowrap",
        ...mediaQueries.oneColumnDown({
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                lineHeight: globalVars.lineHeights.condensed,
            }),
        }),
    });

    return {
        title,
        container,
        statItem,
        statItemSkeleton,
        statItemLink,
        statItemResponsive,
        statData,
        statLabel,
    };
});
