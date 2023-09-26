/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";
import { DeepPartial } from "@reduxjs/toolkit";

// right now, classes here are not dynamic as we don't have categoryList variables yet,
// but once we tackle the ticket https://higherlogic.atlassian.net/browse/VNLA-5076 we will have those classes more dynamic, just like we do with discussion list
export const categoryListClasses = useThemeCache(
    (itemOptionOverrides?: DeepPartial<ICategoryItemOptions>, asTile?: boolean) => {
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

        const listHeadingGroupFirstItem = css({
            "&:before": {
                borderTop: "none",
            },
        });

        const listHeadingGroupLastItem = css({
            marginBottom: 24,
        });

        const message = css({
            marginTop: 8,
            marginBottom: 24,
        });

        const iconWrap = css({
            height: itemOptionOverrides?.imagePlacement === "left" ? 48 : 72,
            overflow: "hidden",
            borderRadius: 6,
        });

        const icon = css({
            height: itemOptionOverrides?.imagePlacement === "left" ? 48 : 72,
            maxHeight: itemOptionOverrides?.imagePlacement === "left" ? 48 : 72,
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

        return {
            listContainer,
            listHeadingGroupFirstItem,
            listHeadingGroupLastItem,
            message,
            iconWrap,
            icon,
            gridContainer,
            gridHeadingWrapper,
            gridGroup,
        };
    },
);
