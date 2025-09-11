/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { WidgetItemContentType } from "@library/homeWidget/WidgetItemOptions";
import { Mixins } from "@library/styles/Mixins";
import { BorderType } from "@library/styles/styleHelpersBorders";
import type CategoriesWidgetItem from "@library/widgets/CategoriesWidget.Item";
import { categoriesWidgetListVariables } from "@library/widgets/CategoriesWidget.List.variables";

export const categoriesWidgetListClasses = useThemeCache(
    (itemOptionsOverrides?: CategoriesWidgetItem.Options, asTile = false) => {
        const vars = categoriesWidgetListVariables(itemOptionsOverrides, asTile);

        const listWithNoSeparators = !asTile && vars.item.options.box.borderType !== BorderType.SEPARATOR;

        const listContainer = css({
            "& > .heading": {
                "&:not(:first-of-type)": {
                    marginTop: 48,
                },

                "&.heading-3": {
                    marginTop: 24,
                    marginBottom: -4,
                    ...(listWithNoSeparators && { marginBottom: 16 }),
                },

                "&.firstItem": {
                    marginTop: 16,
                },

                "& li + .heading-3,": {
                    marginTop: 24,
                },

                "& li + .heading-2,": {
                    marginTop: 48,
                },
                ...(listWithNoSeparators && { marginBottom: 16 }),
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

        const listItem = css({
            ...Mixins.box(vars.item.options.box),
            ...(vars.item.options.box.borderType === BorderType.NONE && { marginBottom: 16 }),
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

        const gridContainerNoMargin = css({
            marginLeft: 0,
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
            itemOptionsOverrides?.contentType === WidgetItemContentType.TitleDescriptionIcon ||
            itemOptionsOverrides?.contentType === WidgetItemContentType.TitleDescriptionImage;

        const isGridItemWithIcon = itemOptionsOverrides?.contentType === WidgetItemContentType.TitleDescriptionIcon;
        const isGridItemWithBackground = itemOptionsOverrides?.contentType === WidgetItemContentType.TitleBackground;
        const itemAlignmentIsLeft = vars.item.options.alignment === "left" ? true : false;
        const isGridItemWithNoBorder = homeWidgetItemVariables().options.box.borderType === BorderType.NONE;

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

        const iconContainerInGridItem = css({
            display: "flex",
            paddingTop: 16,
            justifyContent: "center",
            ...(itemAlignmentIsLeft && isGridItemWithIcon && { paddingLeft: 16, justifyContent: "left" }),
            ...(isGridItemWithNoBorder && { paddingLeft: itemAlignmentIsLeft ? 0 : undefined, paddingBottom: 16 }),
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
            listItem,
            listItemActionButton,
            message,
            iconContainer,
            iconContainerInGridItem,
            icon,
            title,
            gridContainer,
            gridContainerNoMargin,
            gridHeadingWrapper,
            gridGroup,
            gridItem,
            gridItemMetas,
        };
    },
);
