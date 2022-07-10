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
    let listItemWidth = vars.sizing.width;

    if (vars.name.display) {
        listItemWidth += ((vars.name.spacing.left ?? 0) as number) + vars.name.width;
    }

    const list = css({
        "@supports (display: grid)": {
            display: "grid",
            gridTemplateColumns: `repeat(auto-fill, minmax(${listItemWidth}px, 0.5fr))`,
            gridRowGap: `${vars.spacing.vertical}px`,
            gridColumnGap: `${vars.spacing.horizontal}px`,
            justifyItems: "center",
            ...Mixins.margin({
                bottom: vars.spacing.vertical,
            }),
        },

        "@supports not (display: grid)": {
            display: "flex",
            flexWrap: "wrap",
            justifyContent: "space-between",
            ...Mixins.margin({ horizontal: -getPixelNumber((vars.spacing.horizontal! as number) / 2) }),
        },
    });

    const listItem = css({
        "@supports not (display: grid)": {
            ...Mixins.margin({
                bottom: getPixelNumber(vars.spacing.vertical),
                horizontal: getPixelNumber((vars.spacing.horizontal! as number) / 2),
            }),
        },
    });

    return {
        list,
        listItem,
    };
});

export const contributionItemClasses = useThemeCache((vars: ReturnType<typeof contributionItemVariables>) => {
    const link = css({
        display: "inline-flex",
        flexDirection: "row",
        alignItems: "center",
        ...Mixins.font({ ...vars.name.font }),
        ...Mixins.clickable.itemState({
            default: vars.name.font.color,
        }),
        verticalAlign: "middle",
    });
    const imageAndCountWrapper = css({
        position: "relative",
        width: vars.sizing.width,
        height: vars.sizing.width,
    });

    const count = css({
        height: vars.count.height,
        minWidth: vars.count.height,
        position: "absolute",
        top: "-5%",
        left: "unset",
        right: 0,
        transform: `translateX(clamp(${Math.round((vars.sizing.width * 2) / 38)}px, 50%, ${
            (vars.name.spacing.left! as number) / 2
        }px))`,
        backgroundColor: vars.count.backgroundColor,
        ...Mixins.border({
            color: vars.count.borderColor,
            radius: 8,
            width: 1.3,
        }),
        ...Mixins.font({
            size: vars.count.size,
            letterSpacing: "-1px",
        }),
    });

    const image = css({
        width: vars.sizing.width,
        height: vars.sizing.width,
    });

    const name = css({
        width: vars.name.width,
        ...Mixins.margin({ ...vars.name.spacing }),
        display: "-webkit-box",
        WebkitLineClamp: 2,
        wordBreak: "break-word",
        WebkitBoxOrient: "vertical",
        overflow: "hidden",
        textOverflow: "ellipsis",
    });

    return {
        link,
        image,
        imageAndCountWrapper,
        count,
        name,
    };
});
