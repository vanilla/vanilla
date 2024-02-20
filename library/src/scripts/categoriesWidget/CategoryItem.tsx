/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { ListItem } from "@library/lists/ListItem";
import React from "react";
import { createSourceSetValue, getMeta } from "@library/utility/appUtils";
import {
    HomeWidgetItemContentType,
    IHomeWidgetItemOptions,
    homeWidgetItemVariables,
} from "@library/homeWidget/HomeWidgetItem.styles";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import { HomeWidgetItem, IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { ICountResult } from "@library/search/searchTypes";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { CategoryItemMeta } from "@library/categoriesWidget/CategoryItemMeta";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";

export interface ICategoryItemOptions {
    imagePlacement?: IHomeWidgetItemOptions["imagePlacement"];
    contentType?: HomeWidgetItemContentType;
    description?: {
        display?: boolean;
    };
    followButton?: {
        display?: boolean;
    };
    metas?: {
        asIcons?: boolean | string;
        includeSubcategoriesCount?: string[]; // will add subcategories counts to existing category counts
        display?: {
            discussionCount?: boolean;
            commentCount?: boolean;
            postCount?: boolean;
            followerCount?: boolean;
            lastPostName?: boolean;
            lastPostAuthor?: boolean;
            lastPostDate?: boolean;
            subcategories?: boolean;
        };
    };
}

export type ICategoryItemCount = ICountResult & {
    countAll?: number; //case where we include children count as well
};
export interface ICategoryItem extends IHomeWidgetItemProps {
    name: string;
    categoryID: number;
    parentCategoryID?: number;
    depth: number;
    displayAs: string;
    children?: ICategoryItem[];
    noChildCategoriesMessage?: string;
    counts: ICategoryItemCount[];
    lastPost?: Partial<IDiscussion>;
    preferences?: ICategoryPreferences;
}
interface IProps {
    category: ICategoryItem;
    className?: string;
    depth?: number;
    options?: ICategoryItemOptions;
    asTile?: boolean;
    onCategoryFollowChange?: (categoryWithNewPreferences) => void;
    isPreview?: boolean; // preview in layout editor
}

export default function CategoryItem(props: IProps) {
    const { category, depth, options, asTile, onCategoryFollowChange } = props;
    const categoryOptions = {
        ...options,
        display: {
            description: options?.description?.display,
        },
        metas: {
            ...options?.metas,
            asIcons:
                typeof options?.metas?.asIcons === "string"
                    ? options?.metas?.asIcons === "text"
                        ? false
                        : true
                    : options?.metas?.asIcons,
        },
    };

    const currentUser = useCurrentUser();
    const currentUserSignedIn = useCurrentUserSignedIn();

    const classes = categoryListClasses(categoryOptions, asTile);

    const iconUrlSrcSet = category.iconUrlSrcSet ? { srcSet: createSourceSetValue(category.iconUrlSrcSet) } : {};
    const defaultIconUrl = homeWidgetItemVariables().options.defaultIconUrl;

    const showIcon = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
    const showFeaturedImage = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;

    const iconComponent = (
        <div className={classes.iconContainer}>
            <img
                className={classes.icon}
                src={category.iconUrl ?? defaultIconUrl}
                alt={category.name}
                loading="lazy"
                {...iconUrlSrcSet}
            />
        </div>
    );

    // no need to apply metas className if we don't have any meta
    const hasCountsMeta =
        category.counts?.length &&
        category.counts.some((countType) => {
            return countType.count > 0 && options?.metas?.display?.[`${countType.labelCode.slice(0, -1)}Count`];
        });

    // subcommunities widget is also using this component, and its description is not controlled from widget settings but variables
    const description = !category.displayAs || options?.description?.display ? category.description : "";

    if (asTile) {
        return (
            <HomeWidgetItem
                className={classes.gridItem}
                nameClassName={classes.title}
                key={category.categoryID}
                name={category.name}
                to={category.to}
                iconUrl={category.iconUrl}
                imageUrl={category.imageUrl}
                description={description}
                options={categoryOptions}
                metaComponent={
                    <CategoryItemMeta
                        className={cx({ [classes.gridItemMetas]: Boolean(hasCountsMeta) })}
                        category={category}
                        categoryOptions={categoryOptions}
                    />
                }
                iconComponent={
                    showIcon ? <div className={classes.iconContainerInGridItem}>{iconComponent}</div> : undefined
                }
            />
        );
    }

    return (
        <ListItem
            key={category.categoryID}
            url={category.to as string}
            name={category.name}
            nameClassName={classes.title}
            className={cx(classes.listItem, props.className)}
            headingDepth={depth}
            description={description}
            metas={
                <CategoryItemMeta
                    className={cx({ [classes.gridItemMetas]: props.asTile })}
                    category={category}
                    categoryOptions={categoryOptions}
                />
            }
            actions={
                options?.followButton?.display &&
                category.displayAs === "discussions" &&
                currentUserSignedIn &&
                currentUser?.userID &&
                !asTile && (
                    <CategoryFollowDropDown
                        userID={currentUser?.userID}
                        categoryID={category.categoryID}
                        categoryName={category.name}
                        notificationPreferences={category.preferences}
                        emailDigestEnabled={getMeta("emails.digest", false)}
                        emailEnabled={getMeta("emails.enabled")}
                        isCompact
                        preview={props.isPreview}
                        className={classes.listItemActionButton}
                        onPreferencesChange={onCategoryFollowChange}
                    />
                )
            }
            icon={showIcon && iconComponent}
            featuredImage={{ display: showFeaturedImage }}
            image={{ url: category.imageUrl, urlSrcSet: category.imageUrlSrcSet }}
        />
    );
}
