/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { ICategoriesWidgetProps, ICategoryItem } from "@library/categoriesWidget/CategoriesWidget";
import { t } from "@vanilla/i18n";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
    homeWidgetContainerClasses,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetContainer, HomeWidgetGridContainer } from "@library/homeWidget/HomeWidgetContainer";
import { groupCategoryItems } from "@library/categoriesWidget/CategoriesWidget.utils";
import CategoryItem, { ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";
import Heading from "@library/layout/Heading";

export function CategoriesGridView(props: ICategoriesWidgetProps) {
    const { itemData, containerOptions } = props;
    const classes = categoryListClasses();

    const categoryGroups = useMemo(() => {
        return groupCategoryItems(itemData);
    }, [itemData]);

    return (
        <HomeWidgetContainer
            title={props.title}
            subtitle={props.subtitle}
            description={props.description}
            options={{
                ...props.containerOptions,
                displayType: WidgetContainerDisplayType.GRID,
            }}
        >
            <div className={classes.gridContainer}>
                {categoryGroups.length === 0 ? (
                    <div>{t("No categories were found.")}</div>
                ) : (
                    categoryGroups.map((categoryGroup, i) => {
                        return (
                            <CategoryGridItemsGroup
                                itemOptions={props.itemOptions}
                                group={categoryGroup}
                                key={i}
                                containerOptions={containerOptions}
                            />
                        );
                    })
                )}
            </div>
        </HomeWidgetContainer>
    );
}

interface ICategoryGroup {
    itemOptions?: ICategoryItemOptions;
    group: Omit<ICategoryItem, "categoryID" | "to">;
    isFirstItem?: boolean;
    containerOptions?: IHomeWidgetContainerOptions;
}

function CategoryGridItemsGroup(props: ICategoryGroup) {
    const { group, itemOptions, containerOptions } = props;
    const classes = categoryListClasses();
    const isHeading = group.displayAs === "heading";
    const containerClasses = homeWidgetContainerClasses(props.containerOptions);

    if (isHeading && group.depth <= 2) {
        return (
            <>
                <div className={classes.gridHeadingWrapper}>
                    <Heading depth={group.depth + 1}>{group.name}</Heading>
                    {!group.children?.length && <div className={classes.message}>{group.noChildCategoriesMessage}</div>}
                </div>
                {group.children?.map((child, i) => {
                    return (
                        <CategoryGridItemsGroup
                            key={i}
                            group={child}
                            itemOptions={itemOptions}
                            containerOptions={containerOptions}
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
                    }}
                >
                    {group.children?.map((child, i) => {
                        return <CategoryItem key={i} category={child} asTile options={itemOptions} />;
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

export default CategoriesGridView;
