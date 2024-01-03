/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListSortOptions } from "@dashboard/@types/api/discussion";
import { LayoutSectionInfos } from "@dashboard/layout/editor/LayoutSectionInfos";
import {
    IEditableLayoutSpec,
    IEditableLayoutWidget,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { t } from "@vanilla/i18n";

export class LayoutEditorAssetUtils {
    /**
     * Return required asset list per layoutViewType
     */
    public static assetsPerLayoutViewType(layoutViewType?: LayoutViewType): string[] {
        switch (layoutViewType) {
            case "home":
            case "subcommunityHome":
                return [];
            case "discussionList":
                return ["react.asset.discussionList"];
            case "categoryList":
            case "nestedCategoryList":
                return ["react.asset.categoryFollow", "react.asset.categoryList"];
            case "discussionCategoryPage":
                return ["react.asset.categoryFollow", "react.asset.categoryList", "react.asset.discussionList"];
            default:
                return [];
        }
    }

    /**
     * Validate if our layout contains required assets
     */
    public static validateAssets(layoutSpec: IEditableLayoutSpec) {
        const requiredAssets = this.assetsPerLayoutViewType(layoutSpec.layoutViewType);

        let isValid = true;

        requiredAssets.forEach((asset) => {
            let assetExists = false;
            layoutSpec.layout.forEach((spec) => {
                //if we found the asset in any section, thats enough to be valid
                const foundAsset =
                    !assetExists &&
                    LayoutSectionInfos[spec.$hydrate].regionNames.some((region) => {
                        return spec[region] && spec[region].some((child) => child.$hydrate && child.$hydrate === asset);
                    });
                if (foundAsset) {
                    assetExists = true;
                }
            });
            isValid = assetExists;
        });

        return {
            isValid: isValid,
            message: !isValid ? t("Missing required widget") : null,
        };
    }

    /**
     * Return initial hydrate spec for discussionCategoryList layoutViewType
     *  -react.asset.categoryFollow
     *  -react.asset.categoryList
     *  -react.asset.discussionList
     *
     */
    public static categoryAndDiscussionListSection(): IEditableLayoutWidget {
        let children = this.categoryFollow();
        children = children.concat(this.categoryList());
        children = children.concat(this.discussionList());
        return {
            $hydrate: "react.section.1-column",
            children: children,
        };
    }

    /**
     * Return initial hydrate spec for categoryList and nestedCategoryList layoutViewType, category list asset and discussion list asset.
     */
    public static categoryListSection(): IEditableLayoutWidget {
        let children = this.categoryFollow();
        children = children.concat(this.categoryList());
        return {
            $hydrate: "react.section.1-column",
            children: children,
        };
    }

    /**
     * Return initial hydrate spec for discussion list asset.
     */
    public static discussionListSection(): IEditableLayoutWidget {
        return {
            $hydrate: "react.section.1-column",
            children: this.discussionList(),
        };
    }

    /**
     * Return the categoryList asset initial spec.
     * @private
     */
    private static categoryList(): object[] {
        return [
            {
                $hydrate: "react.asset.categoryList",
                apiParams: {
                    filter: "none",
                },
                categoryOptions: {
                    description: {
                        display: true,
                    },
                    followButton: {
                        display: true,
                    },
                    metas: {
                        display: {
                            postCount: true,
                            commentCount: true,
                            discussionCount: true,
                            followerCount: true,
                            lastPostName: true,
                            lastPostAuthor: true,
                            lastPostDate: true,
                            subcategories: true,
                        },
                    },
                },
                title: "Categories",
                titleType: "static",
                descriptionType: "none",
                isAsset: true,
                containerOptions: {
                    displayType: WidgetContainerDisplayType.LIST,
                },
            },
        ];
    }

    /**
     * Return the discussionList asset initial spec.
     * @private
     */
    private static discussionList(): object[] {
        return [
            {
                $hydrate: "react.asset.discussionList",
                apiParams: {
                    includeChildCategories: true,
                    sort: DiscussionListSortOptions.RECENTLY_COMMENTED,
                    slotType: "a",
                },
                discussionOptions: {
                    excerpt: {
                        display: true,
                    },
                    metas: {
                        display: {
                            category: true,
                            commentCount: true,
                            lastCommentDate: true,
                            lastUser: true,
                            score: true,
                            startedByUser: true,
                            unreadCount: true,
                            userTags: true,
                            viewCount: true,
                        },
                    },
                },
                title: "Recent Discussions",
                titleType: "static",
                descriptionType: "none",
                isAsset: true,
            },
        ];
    }

    /**
     * Return the categoryFollow asset initial spec.
     * @private
     */
    private static categoryFollow(): object[] {
        return [
            {
                $hydrate: "react.asset.categoryFollow",
                isAsset: true,
            },
        ];
    }
}
