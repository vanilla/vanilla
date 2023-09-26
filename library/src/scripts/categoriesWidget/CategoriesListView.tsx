/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ICategoriesWidgetProps, ICategoryItem } from "@library/categoriesWidget/CategoriesWidget";
import { List } from "@library/lists/List";
import CategoryItem, { ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";
import { PageBox } from "@library/layout/PageBox";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import { cx } from "@emotion/css";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";

export function CategoriesListView(props: ICategoriesWidgetProps) {
    const classes = categoryListClasses();
    const itemOptions: ICategoryItemOptions = { ...props.itemOptions, imagePlacement: "left" };

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
                {props.itemData.length === 0 ? (
                    <PageBox as="li">{t("No categories were found.")}</PageBox>
                ) : (
                    props.itemData.map((category, i) => {
                        if (category.displayAs === "heading") {
                            return (
                                <CategoryHeadingGroup
                                    key={category.categoryID}
                                    headingCategory={category}
                                    isFirstItem={i === 0}
                                    itemOptions={itemOptions}
                                />
                            );
                        }

                        return (
                            <CategoryItem
                                options={itemOptions}
                                category={category}
                                key={category.categoryID}
                                className={`depth-${category.depth}`}
                                depth={category.depth + 1}
                            />
                        );
                    })
                )}
            </List>
        </HomeWidgetContainer>
    );
}

interface ICategoryHeadingGroup {
    itemOptions?: ICategoryItemOptions;
    headingCategory: ICategoryItem;
    isFirstItem?: boolean;
}

function CategoryHeadingGroup(props: ICategoryHeadingGroup) {
    const { headingCategory, isFirstItem, itemOptions } = props;
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
            <Heading depth={headingCategory.depth + 1} className={isFirstItem ? "firstItem" : ""}>
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
                                key={category.categoryID}
                                headingCategory={category}
                                itemOptions={itemOptions}
                            />
                        );
                    }
                    if (!isHeading) {
                        return (
                            <CategoryItem
                                options={itemOptions}
                                category={category}
                                key={category.categoryID}
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

export default CategoriesListView;
