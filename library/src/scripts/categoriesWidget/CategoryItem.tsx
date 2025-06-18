/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { ListItem } from "@library/lists/ListItem";
import React from "react";
import { createSourceSetValue, getMeta, siteUrl } from "@library/utility/appUtils";
import {
    HomeWidgetItemContentType,
    IHomeWidgetItemOptions,
    homeWidgetItemVariables,
} from "@library/homeWidget/HomeWidgetItem.styles";
import { categoryListClasses } from "@library/categoriesWidget/CategoryList.classes";
import { CommonHomeWidgetItemProps, HomeWidgetItem, IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { ICountResult } from "@library/search/searchTypes";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { CategoryItemMeta } from "@library/categoriesWidget/CategoryItemMeta";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { LocationDescriptor } from "history";
import { IFollowedCategoryNotificationPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";

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

export interface ICategoryItem extends CommonHomeWidgetItemProps {
    to: LocationDescriptor;
    name: string;
    categoryID: number;
    parentCategoryID?: number;
    depth: number;
    displayAs: string;
    children?: ICategoryItem[];
    noChildCategoriesMessage?: string;
    counts: ICategoryItemCount[];
    lastPost?: Partial<IDiscussion>;
    preferences?: IFollowedCategoryNotificationPreferences;
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

    const classes = categoryListClasses.useAsHook(categoryOptions, asTile);

    const iconUrlSrcSet = category.iconUrlSrcSet ? { srcSet: createSourceSetValue(category.iconUrlSrcSet) } : {};
    const defaultIconUrl = homeWidgetItemVariables.useAsHook().options.defaultIconUrl;

    const showIcon = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON;
    const showFeaturedImage = options?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE;

    const iconComponent = (
        <div className={classes.iconContainer}>
            <img
                className={classes.icon}
                src={siteUrl(category.iconUrl ?? defaultIconUrl!)}
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
                        recordID={category.categoryID}
                        userID={currentUser?.userID}
                        name={category.name}
                        notificationPreferences={category.preferences}
                        emailDigestEnabled={getMeta("emails.digest", false)}
                        iconOnly
                        preview={props.isPreview}
                        className={classes.listItemActionButton}
                        onPreferencesChange={onCategoryFollowChange}
                        viewRecordUrl={category.to as string}
                    />
                )
            }
            icon={showIcon && iconComponent}
            featuredImage={{ display: showFeaturedImage }}
            image={{ url: category.imageUrl, urlSrcSet: category.imageUrlSrcSet }}
        />
    );
}
