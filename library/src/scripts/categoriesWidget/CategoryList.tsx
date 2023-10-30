/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ICategoriesWidgetProps } from "@library/categoriesWidget/CategoriesWidget";
import { List } from "@library/lists/List";
import CategoryItem, { ICategoryItem } from "@library/categoriesWidget/CategoryItem";
import { PageBox } from "@library/layout/PageBox";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import { cx } from "@emotion/css";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { ICategoryItemOptions } from "@library/categoriesWidget/CategoryList.variables";
import { updateCategoryFollowCount } from "@library/categoriesWidget/CategoriesWidget.utils";
import cloneDeep from "lodash/cloneDeep";

export function CategoryList(props: ICategoriesWidgetProps) {
    const classes = categoryListClasses();
    const categoryOptions: ICategoryItemOptions = {
        ...props.categoryOptions,
        imagePlacement: "left",
        contentType: props.itemOptions?.contentType,
    };

    const [categories, setCategories] = useState<ICategoryItem[]>(cloneDeep(props.itemData));
    const [categoryWithFollowCountChange, setCategoryWithFollowCountChange] = useState<{
        categoryID: ICategoryItem["categoryID"];
        preferences: ICategoryItem["preferences"];
    } | null>(null);

    useEffect(() => {
        if (categoryWithFollowCountChange) {
            const updatedCategories = updateCategoryFollowCount(
                categories,
                categoryWithFollowCountChange.categoryID,
                categoryWithFollowCountChange.preferences?.["preferences.followed"] ? true : false,
            );

            if (updatedCategories) {
                setCategories(updatedCategories);
            }
            setCategoryWithFollowCountChange(null);
        }
    }, [categoryWithFollowCountChange]);

    const onCategoryFollowChange = (categoryWithNewPreferences: {
        categoryID: ICategoryItem["categoryID"];
        preferences: ICategoryItem["preferences"];
    }) => {
        setCategoryWithFollowCountChange(categoryWithNewPreferences);
    };

    return (
        <HomeWidgetContainer
            title={props.title}
            subtitle={props.subtitle}
            description={props.description}
            options={{
                ...props.containerOptions,
                displayType: WidgetContainerDisplayType.LIST,
            }}
        >
            <List className={classes.listContainer} {...props}>
                {categories.length === 0 ? (
                    <PageBox as="li">{t("No categories were found.")}</PageBox>
                ) : (
                    categories.map((category, i) => {
                        if (category.displayAs === "heading") {
                            return (
                                <CategoryHeadingGroup
                                    key={i}
                                    headingCategory={category}
                                    isFirstItem={i === 0}
                                    options={categoryOptions}
                                    onCategoryFollowChange={onCategoryFollowChange}
                                />
                            );
                        }

                        return (
                            <CategoryItem
                                options={categoryOptions}
                                category={category}
                                key={`${category.categoryID}-${i}`}
                                className={`depth-${category.depth}`}
                                depth={category.depth ? category.depth + 1 : undefined}
                                onCategoryFollowChange={onCategoryFollowChange}
                                isPreview={props.isPreview}
                            />
                        );
                    })
                )}
            </List>
        </HomeWidgetContainer>
    );
}

interface ICategoryHeadingGroup {
    options?: ICategoryItemOptions;
    headingCategory: ICategoryItem;
    isFirstItem?: boolean;
    onCategoryFollowChange: (categoryWithNewPreferences: {
        categoryID: ICategoryItem["categoryID"];
        preferences: ICategoryItem["preferences"];
    }) => void;
}

function CategoryHeadingGroup(props: ICategoryHeadingGroup) {
    const { headingCategory, isFirstItem, options, onCategoryFollowChange } = props;
    const classes = categoryListClasses();
    const childCategories = headingCategory.children;
    const lastItemIndex =
        childCategories?.length &&
        (childCategories[childCategories.length - 1].displayAs === "heading"
            ? childCategories.length - 2
            : childCategories.length - 1);
    // we don't render third level headings, so need to find the right index for first non heading item
    const firstItemIndex =
        headingCategory.depth === 2 && childCategories?.some((child) => child.displayAs === "heading")
            ? childCategories.findIndex((childCategory) => childCategory.displayAs !== "heading")
            : 0;

    return (
        <>
            <Heading
                depth={headingCategory.depth + 1}
                className={cx(
                    { ["firstItem"]: isFirstItem },
                    { [classes.firstLevelHeading]: headingCategory.depth === 1 },
                    { [classes.secondLevelHeading]: headingCategory.depth === 2 },
                )}
            >
                {headingCategory.name}
            </Heading>
            {!childCategories?.length && (
                <div className={classes.message}>{headingCategory.noChildCategoriesMessage}</div>
            )}
            {!!childCategories?.length &&
                childCategories.map((category, i) => {
                    const isHeading = category.displayAs === "heading";

                    if (isHeading && category.depth <= 2) {
                        return (
                            <CategoryHeadingGroup
                                key={i}
                                headingCategory={category}
                                options={options}
                                onCategoryFollowChange={onCategoryFollowChange}
                            />
                        );
                    }
                    if (!isHeading) {
                        return (
                            <CategoryItem
                                onCategoryFollowChange={onCategoryFollowChange}
                                options={options}
                                category={category}
                                key={i}
                                className={cx(
                                    `depth-${category.depth}`,
                                    { [classes.listHeadingGroupFirstItem]: i === firstItemIndex },
                                    {
                                        [classes.listHeadingGroupLastItem]: i === lastItemIndex,
                                    },
                                )}
                                depth={category.depth + 1} // this one is to create appropriate <h/> tag
                            />
                        );
                    }
                })}
        </>
    );
}

export default CategoryList;
