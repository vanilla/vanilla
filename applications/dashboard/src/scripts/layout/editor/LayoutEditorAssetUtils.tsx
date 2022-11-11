/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutSectionInfos } from "@dashboard/layout/editor/LayoutSectionInfos";
import {
    IEditableLayoutSpec,
    IEditableLayoutWidget,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { t } from "@vanilla/i18n";

export class LayoutEditorAssetUtils {
    /**
     * Return required asset list per layoutViewType
     */
    public static assetsPerLayoutViewType(layoutViewType?: LayoutViewType): string[] {
        switch (layoutViewType) {
            case "home":
                return [];
            case "discussionList":
                return ["react.asset.discussionList"];
            case "categoryList":
                return [];
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
     * Return initial hydrate spec for discussion list asset.
     */
    public static discussionList(): IEditableLayoutWidget {
        return {
            $hydrate: "react.section.1-column",
            children: [
                {
                    $hydrate: "react.asset.discussionList",
                    apiParams: {
                        includeChildCategories: true,
                        sort: "-dateInserted",
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
                },
            ],
        };
    }
}
