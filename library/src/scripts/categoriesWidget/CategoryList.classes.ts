/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { ICategoryItemOptions, categoryListVariables } from "@library/categoriesWidget/CategoryList.variables";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { Mixins } from "@library/styles/Mixins";

export const categoryListClasses = useThemeCache((itemOptionsOverrides?: ICategoryItemOptions, asTile = false) => {
    const vars = categoryListVariables(itemOptionsOverrides, asTile);

    const listContainer = css({
        "& > .heading": {
            "&:not(:first-of-type)": {
                marginTop: 48,
            },
            "&.heading-3": {
                marginTop: 24,
                marginBottom: -4,
            },
            "&.firstItem": {
                marginTop: 16,
            },
        },
        "& > .heading-2 + .heading-3": {
            marginTop: 16,
        },
    });

    const firstLevelHeading = css({
        ...Mixins.font(vars.item.heading.firstLevel.font),
    });

    const secondLevelHeading = css({
        ...Mixins.font(vars.item.heading.secondLevel.font),
    });

    const listHeadingGroupFirstItem = css({
        "&:before": {
            borderTop: "none",
        },
    });

    const listHeadingGroupLastItem = css({
        marginBottom: 24,
    });

    const listItemActionButton = css({
        marginTop: 24,
    });

    const title = css({
        ...Mixins.font(vars.item.title.font),
        "&:active, &:hover, &:focus, &.focus-visible": {
            ...Mixins.font(vars.item.title.fontState),
        },
    });

    const description = css({
        ...Mixins.font(vars.item.description.font),
        lineHeight: "18px",
    });

    const message = css({
        marginTop: 8,
        marginBottom: 24,
    });

    const iconContainer = css({
        height: vars.item.options.icon.size,
        overflow: "hidden",
        borderRadius: 6,
    });

    const icon = css({
        height: vars.item.options.icon.size,
        maxHeight: vars.item.options.icon.size,
    });

    const gridContainer = css({
        marginLeft: -16,
        "& > div:first-of-type > .heading-2": {
            marginTop: 16,
        },
    });

    const gridHeadingWrapper = css({
        marginLeft: 16,
        "& > .heading-2": {
            marginTop: 48,
        },
        "& .heading-3": {
            marginTop: 16,
        },
    });

    const gridGroup = css({
        marginTop: 16,
        marginBottom: 32,
    });

    const isGridItemWithIconOrImage =
        itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON ||
        itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;

    const isGridItemWithIcon = itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
    const isGridItemWithBackground = itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_BACKGROUND;
    const itemAlignmentIsLeft = vars.item.options.alignment === "left" ? true : false;

    const gridItem = css({
        "& > div": {
            ...(isGridItemWithIconOrImage && {
                height: "100%",
            }),
            alignItems: itemAlignmentIsLeft && isGridItemWithIcon ? "flex-start" : undefined,
            textAlign: itemAlignmentIsLeft && isGridItemWithBackground ? "left" : "center",
            "& > div, & > div > *": {
                textAlign: itemAlignmentIsLeft && !isGridItemWithBackground ? "left" : "center",
            },
        },
    });

    const gridItemMetas = css({
        ...(isGridItemWithIconOrImage &&
            !isGridItemWithBackground && {
                marginTop: 8,
                marginLeft: -4,
            }),
        ...(isGridItemWithBackground && {
            margin: 4,
        }),
    });

    return {
        listContainer,
        firstLevelHeading,
        secondLevelHeading,
        listHeadingGroupFirstItem,
        listHeadingGroupLastItem,
        listItemActionButton,
        message,
        iconContainer,
        icon,
        title,
        description,
        gridContainer,
        gridHeadingWrapper,
        gridGroup,
        gridItem,
        gridItemMetas,
    };
});
