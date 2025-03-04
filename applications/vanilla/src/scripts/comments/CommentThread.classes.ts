/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { codeMixin } from "@library/content/UserContent.styles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";

export const commentThreadClasses = useThemeCache(() => {
    const title = (centerAligned: boolean = false) => {
        return css({
            marginTop: 16,
            ...(centerAligned && {
                textAlign: "center",
            }),
        });
    };

    const containerWithTopBottomBorder = css({
        borderTop: singleBorder({
            color: ColorsUtils.colorOut(globalVariables().border.color),
            width: 1,
        }),
        borderBottom: singleBorder({
            color: ColorsUtils.colorOut(globalVariables().border.color),
            width: 1,
        }),
        ...Mixins.padding(globalVariables().itemList.padding),
    });

    const closedTag = css({
        ...Mixins.margin({ horizontal: "1em" }),
        verticalAlign: "middle",
    });

    const resolved = css({
        marginInlineStart: 12,
        "& svg": {
            verticalAlign: "middle",
        },
    });

    const reportsTag = css({
        flexShrink: 0,
        inlineMarginStart: 4,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        gap: 4,
        padding: "4px inherit",
        "& svg": {
            transform: "translateY(-1%)",
        },
    });

    const sortPagerRow = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        flexWrap: "wrap",
        gap: 16,
        marginBottom: "6px",
    });

    const topPagerWrapper = css({ display: "flex", alignItems: "center", "& > div": { flex: 1 } });

    const topPager = css({
        paddingTop: 0,
        paddingBottom: 0,
    });

    const trendingTooltip = css({
        display: "flex",
        flexDirection: "column",
        gap: 4,
        ...codeMixin(),
    });

    const trendingMathMl = css({
        fontSize: "16px !important",
    });

    const selectAllCheckBox = css({ "&&": { marginBottom: 6, marginRight: 8 } });

    return {
        title,
        containerWithTopBottomBorder,
        closedTag,
        resolved,
        reportsTag,
        sortPagerRow,
        topPagerWrapper,
        topPager,
        trendingTooltip,
        trendingMathMl,
        selectAllCheckBox,
    };
});
