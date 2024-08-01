/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { ICategoriesWidgetProps } from "@library/categoriesWidget/CategoriesWidget";
import { t } from "@vanilla/i18n";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
    homeWidgetContainerClasses,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetContainer, HomeWidgetGridContainer } from "@library/homeWidget/HomeWidgetContainer";
import { groupCategoryItems } from "@library/categoriesWidget/CategoriesWidget.utils";
import CategoryItem, { ICategoryItem, ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";
import Heading from "@library/layout/Heading";
import { homeWidgetItemClasses, homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import cloneDeep from "lodash-es/cloneDeep";
import { cx } from "@emotion/css";
import { BorderType } from "@library/styles/styleHelpersBorders";

export function CategoryGrid(props: ICategoriesWidgetProps) {
    const { containerOptions, categoryOptions } = props;

    // grid is the default display type
    const isGrid =
        !props.containerOptions?.displayType || props.containerOptions?.displayType === WidgetContainerDisplayType.GRID;

    const categoryDefaultOptions: ICategoryItemOptions = {
        ...categoryOptions,
        contentType: props.itemOptions?.contentType ?? homeWidgetItemVariables().options.contentType,
        imagePlacement: "top",
        //metas for grid/carousel are predefined and always rendered as icons
        metas: {
            ...categoryOptions?.metas,
            asIcons: true,
            display: {
                ...categoryOptions?.metas?.display,
                postCount: categoryOptions?.metas?.display?.postCount,
                discussionCount: categoryOptions?.metas?.display?.discussionCount,
                commentCount: categoryOptions?.metas?.display?.commentCount,
                followerCount: categoryOptions?.metas?.display?.followerCount,
                lastPostName: false,
                lastPostAuthor: false,
                lastPostDate: false,
                subcategories: false,
            },
        },
    };

    const classes = categoryListClasses(categoryDefaultOptions, true);

    const itemData = cloneDeep(props.itemData);

    // for grid view with headings
    const categoryGroups = useMemo(() => {
        return groupCategoryItems(itemData);
    }, [itemData]);

    // for carousel, no headings
    const discussionCategories = itemData.filter((item) => item.displayAs !== "heading");

    return (
        <HomeWidgetContainer
            title={props.title}
            subtitle={props.subtitle}
            description={props.description}
            options={{
                ...props.containerOptions,
                displayType: isGrid ? WidgetContainerDisplayType.GRID : WidgetContainerDisplayType.CAROUSEL,
            }}
        >
            {isGrid ? (
                <div className={cx(classes.gridContainer, { [classes.gridContainerNoMargin]: itemData.length === 0 })}>
                    {itemData.length === 0 ? (
                        <div>{t("No categories were found.")}</div>
                    ) : (
                        categoryGroups.map((categoryGroup, i) => {
                            return (
                                <CategoryGridItemsGroup
                                    options={categoryDefaultOptions}
                                    group={categoryGroup as ICategoryGroup["group"]}
                                    key={i}
                                    containerOptions={containerOptions}
                                    topLevelHeadingDepth={
                                        props.itemData.find((item) => item.displayAs === "heading")?.depth
                                    }
                                />
                            );
                        })
                    )}
                </div>
            ) : (
                discussionCategories.map((item, i) => {
                    return (
                        <CategoryItem
                            key={i}
                            category={item}
                            asTile
                            options={categoryDefaultOptions}
                            className={homeWidgetItemClasses().root}
                        />
                    );
                })
            )}
        </HomeWidgetContainer>
    );
}

interface ICategoryGroup {
    options?: ICategoryItemOptions;
    group: Omit<ICategoryItem, "categoryID" | "to" | "counts">;
    isFirstItem?: boolean;
    containerOptions?: IHomeWidgetContainerOptions;
    topLevelHeadingDepth?: number;
}

function CategoryGridItemsGroup(props: ICategoryGroup) {
    const { group, options, containerOptions } = props;
    const classes = categoryListClasses(options, true);
    const isHeading = group.displayAs === "heading";
    const containerClasses = homeWidgetContainerClasses(props.containerOptions);

    // determine what is the top level heading depth, its 1 if we are on categories root page, but we can be nested somewhere in the categories tree
    const topLevelHeadingDepth = props.topLevelHeadingDepth ?? 1;

    if (isHeading && group.depth <= topLevelHeadingDepth + 1) {
        return (
            <>
                <div className={classes.gridHeadingWrapper}>
                    <Heading
                        depth={group.depth === topLevelHeadingDepth ? 2 : 3} // h2 or h3
                        className={cx(
                            { [classes.firstLevelHeading]: group.depth === topLevelHeadingDepth },
                            { [classes.secondLevelHeading]: group.depth === topLevelHeadingDepth + 1 },
                        )}
                    >
                        {group.name}
                    </Heading>
                    {!group.children?.length && <div className={classes.message}>{group.noChildCategoriesMessage}</div>}
                </div>
                {group.children?.map((child, i) => {
                    return (
                        <CategoryGridItemsGroup
                            key={i}
                            group={child}
                            options={options}
                            containerOptions={containerOptions}
                            topLevelHeadingDepth={topLevelHeadingDepth}
                        />
                    );
                })}
            </>
        );
    }
    if (group.displayAs === "gridItemsGroup") {
        //when grid items don't fill all the container space we need to fill that space with extra spacers so the flexBox do't break, just like we do in HomeWidget
        let extraSpacerItemCount = 0;
        if (group.children && group.children.length < (containerOptions?.maxColumnCount ?? 3)) {
            extraSpacerItemCount = (containerOptions?.maxColumnCount ?? 3) - group.children.length;
        }
        return (
            <div className={classes.gridGroup}>
                <HomeWidgetGridContainer
                    options={{
                        displayType: WidgetContainerDisplayType.GRID,
                        maxColumnCount: containerOptions?.maxColumnCount,
                        borderType: BorderType.NONE,
                    }}
                >
                    {group.children?.map((child, i) => {
                        return (
                            <CategoryItem
                                key={i}
                                category={child}
                                asTile
                                options={options}
                                className={homeWidgetItemClasses().root}
                            />
                        );
                    })}
                    {[...new Array(extraSpacerItemCount)].map((_, i) => {
                        return <div key={"spacer-" + i} className={containerClasses.gridItemSpacer}></div>;
                    })}
                </HomeWidgetGridContainer>
            </div>
        );
    }
    return <></>;
}

export default CategoryGrid;
