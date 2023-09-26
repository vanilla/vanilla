/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { ListItem } from "@library/lists/ListItem";
import React from "react";
import { createSourceSetValue } from "@library/utility/appUtils";
import { ICategoryItem } from "@library/categoriesWidget/CategoriesWidget";
import {
    HomeWidgetItemContentType,
    homeWidgetItemVariables,
    IHomeWidgetItemOptions,
} from "@library/homeWidget/HomeWidgetItem.styles";
import { DeepPartial } from "@reduxjs/toolkit";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { ResultMeta } from "@library/result/ResultMeta";

export type ICategoryItemOptions = Pick<DeepPartial<IHomeWidgetItemOptions>, "imagePlacement" | "contentType">;

interface IProps {
    category: ICategoryItem;
    className?: string;
    depth?: number;
    options?: ICategoryItemOptions;
    asTile?: boolean;
    displayAs?: string;
}

export default function CategoryItem(props: IProps) {
    const { category, depth, options } = props;
    const classes = categoryListClasses(props.options);

    const iconUrlSrcSet = category.iconUrlSrcSet ? { srcSet: createSourceSetValue(category.iconUrlSrcSet) } : {};
    const defaultIconUrl = homeWidgetItemVariables().options.defaultIconUrl;

    const showIcon = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
    const showFeaturedImage = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;
    if (props.asTile) {
        return (
            <HomeWidgetItem
                key={category.categoryID}
                description={category.description}
                options={options}
                {...category}
            />
        );
    }

    return (
        <ListItem
            url={category.to as string}
            name={category.name}
            className={cx(props.className)}
            headingDepth={depth}
            description={category.description}
            metas={<ResultMeta counts={category.counts} />}
            icon={
                showIcon && (
                    <div className={classes.iconWrap}>
                        <img
                            className={classes.icon}
                            src={category.iconUrl ?? defaultIconUrl}
                            alt={category.name}
                            loading="lazy"
                            {...iconUrlSrcSet}
                        />
                    </div>
                )
            }
            featuredImage={{ display: showFeaturedImage }}
            image={{ url: category.imageUrl, urlSrcSet: category.imageUrlSrcSet }}
        />
    );
}
