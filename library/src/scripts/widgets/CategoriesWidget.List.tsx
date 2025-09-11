/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ICategoriesWidgetProps } from "@library/widgets/CategoriesWidget";
import { List } from "@library/lists/List";
import CategoriesWidgetItem from "@library/widgets/CategoriesWidget.Item";
import { PageBox } from "@library/layout/PageBox";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { categoriesWidgetListClasses } from "@library/widgets/CategoriesWidget.List.classes";
import { cx } from "@emotion/css";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { updateCategoryFollowCount } from "@library/widgets/CategoriesWidget.utils";
import cloneDeep from "lodash-es/cloneDeep";
import isEqual from "lodash-es/isEqual";

export function CategoriesWidgetList(props: ICategoriesWidgetProps) {
    const classes = categoriesWidgetListClasses.useAsHook();
    const categoryOptions: CategoriesWidgetItem.Options = {
        ...props.categoryOptions,
        imagePlacement: "left",
        contentType: props.itemOptions?.contentType,
    };

    const itemData = cloneDeep(props.itemData);

    const [categories, setCategories] = useState<CategoriesWidgetItem.Item[]>(itemData);
    const [categoryWithFollowCountChange, setCategoryWithFollowCountChange] = useState<{
        categoryID: CategoriesWidgetItem.Item["categoryID"];
        preferences: CategoriesWidgetItem.Item["preferences"];
    } | null>(null);

    useEffect(() => {
        // reset the state if itemData from props is changed
        if (!isEqual(categories, itemData)) {
            setCategories(itemData);
        }

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
    }, [categoryWithFollowCountChange, itemData]);

    const onCategoryFollowChange = (categoryWithNewPreferences: {
        categoryID: CategoriesWidgetItem.Item["categoryID"];
        preferences: CategoriesWidgetItem.Item["preferences"];
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
                                    topLevelHeadingDepth={
                                        props.itemData.find((item) => item.displayAs === "heading")?.depth
                                    }
                                />
                            );
                        }

                        return (
                            <CategoriesWidgetItem
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

interface ICategoryHeadingGroupProps {
    options?: CategoriesWidgetItem.Options;
    headingCategory: CategoriesWidgetItem.Item;
    isFirstItem?: boolean;
    onCategoryFollowChange: (categoryWithNewPreferences: {
        categoryID: CategoriesWidgetItem.Item["categoryID"];
        preferences: CategoriesWidgetItem.Item["preferences"];
    }) => void;
    topLevelHeadingDepth?: number;
}

function CategoryHeadingGroup(props: ICategoryHeadingGroupProps) {
    const { headingCategory, isFirstItem, options, onCategoryFollowChange } = props;
    const classes = categoriesWidgetListClasses.useAsHook();
    const childCategories = headingCategory.children;

    // determine what is the top level heading depth, its 1 if we are on categories root page, but we can be nested somewhere in the categories tree
    const topLevelHeadingDepth = props.topLevelHeadingDepth ?? 1;

    const lastItemIndex =
        childCategories?.length &&
        (childCategories[childCategories.length - 1].displayAs === "heading"
            ? childCategories.length - 2
            : childCategories.length - 1);

    // we don't render third level headings, so need to find the right index for first non heading item
    const firstItemIndex =
        headingCategory.depth === topLevelHeadingDepth + 1 &&
        childCategories?.some((child) => child.displayAs === "heading")
            ? childCategories.findIndex((childCategory) => childCategory.displayAs !== "heading")
            : 0;

    return (
        <>
            <Heading
                depth={headingCategory.depth === topLevelHeadingDepth ? 2 : 3} // h2 or h3
                className={cx(
                    { ["firstItem"]: isFirstItem },
                    { [classes.firstLevelHeading]: headingCategory.depth === topLevelHeadingDepth },
                    { [classes.secondLevelHeading]: headingCategory.depth === topLevelHeadingDepth + 1 },
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

                    if (isHeading && category.depth <= topLevelHeadingDepth + 1) {
                        return (
                            <CategoryHeadingGroup
                                key={i}
                                headingCategory={category}
                                options={options}
                                onCategoryFollowChange={onCategoryFollowChange}
                                topLevelHeadingDepth={topLevelHeadingDepth}
                            />
                        );
                    }
                    if (!isHeading) {
                        return (
                            <CategoriesWidgetItem
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

export default CategoriesWidgetList;
