/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { FeaturedCollections } from "@library/featuredCollections/FeaturedCollections";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { CollectionRecordTypes, ICollection } from "@library/featuredCollections/Collections.variables";

export default {
    title: "Widgets/FeaturedCollections",
    excludeStories: ["fakeCollection"],
};

export const fakeCollection: ICollection = {
    collectionID: 1,
    name: "Storybook Collection",
    records: fakeDiscussions.map((record) => ({
        recordID: record.discussionID,
        recordType: CollectionRecordTypes.DISCUSSION,
        record,
    })),
};

export const WithoutFeaturedImages = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        const subtitle = "Without Featured Images";

        return (
            <>
                <FeaturedCollections title="List View" subtitle={subtitle} collection={fakeCollection} />
                <FeaturedCollections
                    title="Grid View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.GRID,
                    }}
                />
                <FeaturedCollections
                    title="Carousel View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.CAROUSEL,
                    }}
                />
                <FeaturedCollections
                    title="Links View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.LINK,
                    }}
                />
            </>
        );
    },
);

export const WithFeaturedImages = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        const subtitle = "With Featured Images";
        const displayOptions = {
            featuredImage: {
                display: true,
            },
        };

        return (
            <>
                <FeaturedCollections
                    title="List View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={displayOptions}
                />
                <FeaturedCollections
                    title="Grid View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.GRID,
                        ...displayOptions,
                    }}
                />
                <FeaturedCollections
                    title="Carousel View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.CAROUSEL,
                        ...displayOptions,
                    }}
                />
                <FeaturedCollections
                    title="Links View"
                    subtitle={subtitle}
                    collection={fakeCollection}
                    options={{
                        displayType: WidgetContainerDisplayType.LINK,
                        ...displayOptions,
                    }}
                />
            </>
        );
    },
);
