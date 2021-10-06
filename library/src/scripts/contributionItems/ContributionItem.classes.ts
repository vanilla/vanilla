/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { getPixelNumber } from "@library/styles/styleUtils";
import { contributionItemVariables } from "@library/contributionItems/ContributionItem.variables";

export const contributionItemListClasses = useThemeCache((vars: ReturnType<typeof contributionItemVariables>) => {
    const list = css({
        display: "flex",
        flexWrap: "wrap",
    });

    const listItem = css({
        ...Mixins.margin({
            right: getPixelNumber(vars.spacing.horizontal),
            bottom: getPixelNumber(vars.spacing.vertical),
        }),
    });

    return {
        list,
        listItem,
    };
});

export const contributionItemClasses = useThemeCache((vars: ReturnType<typeof contributionItemVariables>) => {
    const link = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });
    const itemHasCount = css({
        position: "relative",
        width: vars.sizing.width,
        height: vars.sizing.width,
    });
    const count = css({
        top: 0,
        left: 27,
        right: "unset",
        height: 18,
        backgroundColor: vars.count.backgroundColor,
        ...Mixins.border({
            color: vars.count.borderColor,
            radius: 8,
            width: 1.3,
        }),
        ...Mixins.font({
            size: vars.count.size,
        }),
    });

    const image = css({
        width: vars.sizing.width,
        height: vars.sizing.width,
    });

    const name = css({
        ...Mixins.font({ ...vars.name.font }),
        ...Mixins.margin({ ...vars.name.spacing }),
    });

    return {
        link,
        image,
        itemHasCount,
        count,
        name,
    };
});
