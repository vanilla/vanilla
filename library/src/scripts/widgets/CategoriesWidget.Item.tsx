/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { IHomeWidgetItemOptions, widgetItemContentTypeToImageType } from "@library/homeWidget/WidgetItemOptions";
import { WidgetItemContentType } from "@library/homeWidget/WidgetItemOptions";
import { ListItem } from "@library/lists/ListItem";
import { ICountResult } from "@library/search/searchTypes";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import { createSourceSetValue, getMeta, siteUrl, type ImageSourceSet } from "@library/utility/appUtils";
import { CategoryItemFragmentContext } from "@library/widget-fragments/CategoryItemFragment.context";
import { CategoriesWidgetItemMeta } from "@library/widgets/CategoriesWidget.ItemMeta";
import { categoriesWidgetListClasses } from "@library/widgets/CategoriesWidget.List.classes";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import type { CategoryDisplayAs, ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import type CategoryItemFragmentInjectable from "@vanilla/injectables/CategoryItemFragment";
import { LocationDescriptor } from "history";

namespace CategoriesWidgetItem {
    export interface Options {
        imagePlacement?: IHomeWidgetItemOptions["imagePlacement"];
        contentType?: WidgetItemContentType;
        description?: {
            display?: boolean;
        };
        followButton?: {
            display?: boolean;
        };
        metas?: {
            asIcons?: "text" | "icon" | boolean;
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

    export type Count = ICountResult & {
        countAll?: number; //case where we include children count as well
    };

    export interface Item extends Omit<ICategory, "children" | "displayAs"> {
        to: LocationDescriptor;
        children?: CategoriesWidgetItem.Item[];
        noChildCategoriesMessage?: string;
        displayAs: CategoryDisplayAs | "gridItemsGroup";
        counts: CategoriesWidgetItem.Count[];
        imageUrl?: string;
        imageUrlSrcSet?: ImageSourceSet;
    }
}

interface IProps {
    category: CategoriesWidgetItem.Item;
    className?: string;
    depth?: number;
    options?: CategoriesWidgetItem.Options;
    asTile?: boolean;
    onCategoryFollowChange?: (categoryWithNewPreferences) => void;
    isPreview?: boolean; // preview in layout editor
}

function CategoriesWidgetItem(props: IProps) {
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

    const classes = categoriesWidgetListClasses.useAsHook(categoryOptions, asTile);

    const iconUrlSrcSet = category.iconUrlSrcSet ? { srcSet: createSourceSetValue(category.iconUrlSrcSet) } : {};
    const defaultIconUrl = homeWidgetItemVariables.useAsHook().options.defaultIconUrl;

    const showIcon = options?.contentType === WidgetItemContentType.TitleDescriptionIcon;
    const showFeaturedImage = options?.contentType === WidgetItemContentType.TitleDescriptionImage;

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

    const CustomFragmentImpl = useFragmentImpl<CategoryItemFragmentInjectable.Props>("CategoryItemFragment");
    if (CustomFragmentImpl !== null) {
        return (
            <CategoryItemFragmentContext.Provider
                value={{
                    categoryItem: category,
                    options: categoryOptions,
                    isPreview: props.isPreview,
                    onCategoryFollowChange: onCategoryFollowChange,
                }}
            >
                <CustomFragmentImpl
                    categoryItem={category}
                    options={categoryOptions}
                    imageType={widgetItemContentTypeToImageType(
                        options?.contentType ?? WidgetItemContentType.TitleDescription,
                    )}
                />
            </CategoryItemFragmentContext.Provider>
        );
    }

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
                    <CategoriesWidgetItemMeta
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
                <CategoriesWidgetItemMeta
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

export default CategoriesWidgetItem;
