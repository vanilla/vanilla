/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FeaturedCollections } from "@library/featuredCollections/FeaturedCollections";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { CollectionRecordTypes, ICollection } from "@library/featuredCollections/Collections.variables";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

export default {
    title: "Widgets/FeaturedCollections",
    excludeStories: ["fakeCollection"],
};

export const fakeCollection: ICollection = {
    collectionID: 1,
    name: "Storybook Collection",
    dateInserted: "2020-10-06T15:30:44+00:00",
    dateUpdated: "2020-10-06T15:30:44+00:00",
    records: DiscussionFixture.fakeDiscussions.map((record) => ({
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
