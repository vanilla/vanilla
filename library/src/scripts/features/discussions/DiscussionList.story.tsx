/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { STORY_IPSUM_MEDIUM, STORY_USER, STORY_ME_ADMIN, STORY_TAGS, STORY_IMAGE } from "@library/storybook/storyData";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { LoadStatus } from "@library/@types/api/core";
import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import { setMeta } from "@library/utility/appUtils";

import keyBy from "lodash/keyBy";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { StoryHeading } from "@library/storybook/StoryHeading";
import DiscussionListAsset from "@library/features/discussions/DiscussionListAsset";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { DiscussionListAssetHeader } from "@library/features/discussions/DiscussionListAssetHeader";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import QuickLinks from "@library/navigation/QuickLinks";
import { DiscussionGridView } from "@library/features/discussions/DiscussionGridView";

export default {
    title: "Components/DiscussionLists",
    excludeStories: ["fakeDiscussions"],
};

const dummyUserFragment = STORY_USER;
const dummyTags = STORY_TAGS;

setMeta("ui.useAdminCheckboxes", true);

const commonFields = {
    dateInserted: "2021-02-11 17:51:15",
    dateUpdated: "2021-02-03 17:51:15",
    type: "discussion",
    pinned: false,
    insertUserID: dummyUserFragment.userID,
    insertUser: dummyUserFragment,
    lastUser: dummyUserFragment,
    updateUser: dummyUserFragment,
    closed: false,
    score: 0,
    unread: false,
    countUnread: 0,
    bookmarked: false,
    categoryID: 123,
};

export const fakeDiscussions = [
    {
        ...commonFields,
        url: "#",
        canonicalUrl: "#",
        name: "Unresolved Discussion",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 10,
        countViews: 10,
        countComments: 0,
        dateLastComment: "2021-02-17 17:51:15",
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-2 17:51:15",
        type: "discussion",
        pinned: true,
        score: 2,
        resolved: false,
    },
    {
        ...commonFields,
        url: "#",
        canonicalUrl: "#",
        name: "Resolved Discussion",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 2,
        countViews: 200,
        countComments: 1299,
        closed: true,
        category: {
            categoryID: 123,
            name: "Product Ideas",
            url: "#",
        },
        resolved: true,
    },
    {
        ...commonFields,
        url: "#",
        canonicalUrl: "#",
        name: "With everything",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 5,
        tags: dummyTags,
        countViews: 1029,
        countComments: 11,
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-11 17:51:15",
        unread: true,
        type: "idea",
        reactions: [
            { urlcode: ReactionUrlCode.UP, reactionValue: 1, hasReacted: false },
            { urlcode: ReactionUrlCode.DOWN, reactionValue: -1, hasReacted: true },
        ],
        score: 22,
    },
    {
        ...commonFields,
        url: "#",
        canonicalUrl: "#",
        name: "This is an idea",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 55,
        countViews: 1011,
        countComments: 2,
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-11 17:51:15",
        unread: true,
        type: "idea",
        reactions: [{ urlcode: ReactionUrlCode.UP, reactionValue: 1, hasReacted: false }],
        score: 333,
    },
];

const loggedInStoreState = {
    users: {
        current: {
            data: STORY_ME_ADMIN,
        },
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                isAdmin: true,
                permissions: [],
            },
        },
    },
    discussions: {
        discussionsByID: keyBy(fakeDiscussions, "discussionID"),
    },
};

const chromaticParams = {
    chromatic: { diffThreshold: 0.1 },
};

export const Default = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

Default.parameters = chromaticParams;

export const Theme = storyWithConfig(
    {
        themeVars: {
            listItem: {
                options: {
                    iconPosition: ListItemIconPosition.DEFAULT,
                },
            },
            tags: {
                background: {
                    color: "#E7F0F7",
                },
                font: {
                    weight: "bold",
                },
                border: {
                    style: "none",
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const CertainMetasHidden = storyWithConfig(
    {
        themeVars: {
            discussionList: {
                item: {
                    metas: {
                        display: {
                            startedByUser: false,
                            lastUser: false,
                            viewCount: false,
                        },
                    },
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const MetasRenderedAsIcons = storyWithConfig(
    {
        themeVars: {
            discussionList: {
                item: {
                    metas: {
                        asIcons: true,
                    },
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const UserIconInMetas = storyWithConfig(
    {
        themeVars: {
            contentBoxes: {
                global: {
                    depth2: {
                        borderType: BorderType.SEPARATOR,
                        border: {
                            radius: "0",
                        },
                    },
                },
            },
            listItem: {
                options: {
                    iconPosition: ListItemIconPosition.META,
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

UserIconInMetas.parameters = chromaticParams;

export const UserIconInMetasWithBorder = storyWithConfig(
    {
        themeVars: {
            global: {
                contentBoxes: {
                    depth2: {
                        borderType: BorderType.BORDER,
                        border: {
                            radius: "0",
                        },
                    },
                },
            },
            listItem: {
                options: {
                    iconPosition: ListItemIconPosition.META,
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const UserIconInMetasWithoutBorder = storyWithConfig(
    {
        themeVars: {
            global: {
                contentBoxes: {
                    depth2: {
                        borderType: BorderType.NONE,
                        spacing: {
                            bottom: 15,
                        },
                    },
                },
            },
            listItem: {
                options: {
                    iconPosition: ListItemIconPosition.META,
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const IconHidden = storyWithConfig(
    {
        themeVars: {
            global: {
                contentBoxes: {
                    depth2: {
                        borderType: BorderType.SEPARATOR,
                        border: {
                            radius: "0",
                        },
                    },
                },
            },
            listItem: {
                options: {
                    iconPosition: ListItemIconPosition.HIDDEN,
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <DiscussionListView discussions={fakeDiscussions}></DiscussionListView>
            </StoryContent>
        );
    },
);

export const AsGridVariant = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <DiscussionsWidget
                discussions={fakeDiscussions}
                apiParams={{ discussionID: fakeDiscussions[0].discussionID }}
                containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
            />
        );
    },
);

export const AsCarouselVariant = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <DiscussionsWidget
                discussions={fakeDiscussions}
                apiParams={{ discussionID: fakeDiscussions[0].discussionID }}
                containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
            />
        );
    },
);

export const AsSimpleLinks = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <DiscussionsWidget
                title="My Discussions"
                discussions={fakeDiscussions}
                apiParams={{ discussionID: fakeDiscussions[0].discussionID }}
                containerOptions={{ displayType: WidgetContainerDisplayType.LINK }}
            />
        );
    },
);

export const FeaturedImage = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => (
        <>
            <StoryHeading>List View</StoryHeading>
            <DiscussionsWidget
                discussions={fakeDiscussions}
                apiParams={{
                    discussionID: fakeDiscussions[0].discussionID,
                    featuredImage: true,
                    fallbackImage: STORY_IMAGE,
                }}
                containerOptions={{ displayType: WidgetContainerDisplayType.LIST }}
            />
            <StoryHeading>Grid View</StoryHeading>
            <DiscussionsWidget
                discussions={fakeDiscussions}
                apiParams={{
                    discussionID: fakeDiscussions[0].discussionID,
                    featuredImage: true,
                    fallbackImage: STORY_IMAGE,
                }}
                containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
            />
            <StoryHeading>Carousel View</StoryHeading>
            <DiscussionsWidget
                discussions={fakeDiscussions}
                apiParams={{
                    discussionID: fakeDiscussions[0].discussionID,
                    featuredImage: true,
                    fallbackImage: STORY_IMAGE,
                }}
                containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
            />
        </>
    ),
);

// Using a story only component for to use the mock data since the real DiscussionListAsset component does it's own fetching
const DiscussionListStory = (props) => {
    const mockProps = {
        ...props,
        apiParams: {
            ...props.apiParams,
            discussionID: fakeDiscussions[0].discussionID,
        },
    };

    const discussionOptions = {
        ...props.discussionOptions,
        ...(props.apiParams?.featuredImage && {
            featuredImage: {
                display: true,
                fallbackImage: props.apiParams?.fallbackImage,
            },
        }),
    };

    const isLink = mockProps.containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const assetHeader = (
        <DiscussionListAssetHeader
            discussionIDs={fakeDiscussions.map(({ discussionID }) => discussionID)}
            noCheckboxes={mockProps.noCheckboxes || isLink}
            categoryFollowEnabled
            categoryFollowFilter="all"
            onCategoryFollowFilterChange={() => null}
            paginationProps={{ totalResults: fakeDiscussions.length }}
        />
    );
    const assetFooter = <NumberedPager totalResults={fakeDiscussions.length} />;

    if (mockProps.isList) {
        return (
            <HomeWidgetContainer
                options={{
                    ...mockProps.containerOptions,
                    isGrid: false,
                }}
                extraHeader={assetHeader}
            >
                <DiscussionListView discussions={fakeDiscussions} discussionOptions={discussionOptions} />
                {assetFooter}
            </HomeWidgetContainer>
        );
    }

    if (isLink) {
        return (
            <div>
                <QuickLinks
                    links={fakeDiscussions.map(({ name, url }, index) => ({
                        id: index.toString(),
                        name,
                        url,
                    }))}
                    containerOptions={mockProps.containerOptions}
                    extraHeader={assetHeader}
                />
                {assetFooter}
            </div>
        );
    }

    return (
        <div>
            <DiscussionGridView
                {...mockProps}
                discussionOptions={discussionOptions}
                discussions={fakeDiscussions}
                assetHeader={assetHeader}
            />
            {assetFooter}
        </div>
    );
};

export const AsAssetWithHeader = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => (
        <>
            <StoryHeading>ListView</StoryHeading>
            <DiscussionListStory isList />
            <StoryHeading>Grid View, with featured images and default fallback image</StoryHeading>
            <DiscussionListStory
                apiParams={{ featuredImage: true }}
                containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
            />
            <StoryHeading>Grid View, no featured image, no checkboxes</StoryHeading>
            <DiscussionListStory containerOptions={{ displayType: WidgetContainerDisplayType.GRID }} noCheckboxes />
            <StoryHeading>Carousel, with featured images and set fallback image</StoryHeading>
            <DiscussionListStory
                apiParams={{ featuredImage: true, fallbackImage: STORY_IMAGE }}
                containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
            />
            <StoryHeading>Simple Links</StoryHeading>
            <DiscussionListStory containerOptions={{ displayType: WidgetContainerDisplayType.LINK }} />
        </>
    ),
);
