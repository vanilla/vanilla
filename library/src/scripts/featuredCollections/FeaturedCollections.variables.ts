/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/styleUtils";
import { DeepPartial } from "redux";
import { IFeaturedCollectionsOptions } from "@library/featuredCollections/FeaturedCollections";
import { IThemeVariables } from "@library/theming/themeReducer";
import { homeWidgetContainerVariables } from "@library/homeWidget/HomeWidgetContainer.styles";
import { RecordID } from "@vanilla/utils";
import { IImage } from "@library/@types/api/core";

export const CONFIG_FEATURED_COLLECTIONS = "labs.featuredCollections";

export enum CollectionRecordTypes {
    ARTICLE = "article",
    CATEGORY = "category",
    DISCUSSION = "discussion",
    EVENT = "event",
    GROUPS = "groups",
    KNOWLEDGE_BASE = "knowledgeBase",
}

export interface IFeaturedCollectionRecord {
    name: string;
    excerpt?: string;
    url: string;
    image?: IImage;
}

export interface ICollectionRecord {
    recordID: RecordID;
    recordType: CollectionRecordTypes;
    sort?: number;
    record?: IFeaturedCollectionRecord;
}

export interface ICollection {
    collectionID: RecordID;
    name: string;
    records: ICollectionRecord[];
    insertUserID?: RecordID;
    updateUserID?: RecordID;
    dateInserted?: Date;
    dateUpdated?: Date;
}

/**
 * @varGroup featuredCollections
 * @description Featured Collections is a curated collection of posts to bring more focus to
 */
export const featuredCollectionsVariables = useThemeCache(
    (overrides?: DeepPartial<IFeaturedCollectionsOptions>, forcedVars?: IThemeVariables) => {
        const makeThemeVars = variableFactory("featuredCollections");
        const defaultContainerOptions = homeWidgetContainerVariables().options;

        /**
         * @varGroup featuredCollections.options
         * @title Featured Collections - Options
         */
        const options = makeThemeVars("options", defaultContainerOptions, overrides);

        /**
         * @varGroup featuredCollections.featuredImage
         * @title Featured Collections - Featured Image
         */
        const featuredImage = makeThemeVars("featuredImage", {
            /**
             * @var featuredCollections.featuredImage.display
             * @title Featured Collections - Featured Image - Display
             * @type boolean
             */
            display: overrides?.featuredImage?.display ?? false,
            /**
             * @var featuredCollections.featuredImage.fallbackImage
             * @title Featured Collections - Featured Image - Fallback Image
             * @type string
             * @format url
             */
            fallbackImage: overrides?.featuredImage?.fallbackImage ?? undefined,
        });

        return { options, featuredImage };
    },
);
