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

    const container = css(
        {
            ...Mixins.padding({
                vertical: globalVars.spacer.size,
            }),
            ...Mixins.flex.middle(),
        },
        mediaQueries.oneColumnDown({
            flexDirection: "column",
        }),
    );

    const stat = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        maxWidth: percent(50),
        ...Mixins.font({
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        }),
    });

    const statLink = css({
        "&:hover, &:focus, &:active, &.focus-visible": {
            ...Mixins.font({
                color: globalVars.mainColors.primary,
            }),
        },
    });

    const hasBorder = css(
        {
            borderRight: singleBorder(),
            ...Mixins.padding({
                horizontal: globalVars.spacer.size,
            }),
            flexGrow: 1,
            "&:last-child": {
                border: "none",
            },
        },
        mediaQueries.oneColumnDown({
            borderRight: "none",
            borderBottom: singleBorder(),
            ...Mixins.padding({
                vertical: globalVars.spacer.size,
            }),
        }),
    );

    const statItem = css({
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
        ...mediaQueries.oneColumnDown({
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                lineHeight: globalVars.lineHeights.condensed,
            }),
        }),
        whiteSpace: "nowrap",
    });

    const afterLink = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "normal"),
        }),
        ...Mixins.flex.middleLeft(),
        justifyContent: "flex-end",
        marginTop: globalVars.gutter.half,
    });

    return {
        title,
        container,
        stat,
        statLink,
        hasBorder,
        statItem,
        statLabel,
        afterLink,
    };
});
