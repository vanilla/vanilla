/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_IMAGE, STORY_IPSUM_MEDIUM, STORY_ME_ADMIN, STORY_TAGS, STORY_USER } from "@library/storybook/storyData";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { setMeta } from "@library/utility/appUtils";
import React, { useState } from "react";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { DiscussionGridView } from "@library/features/discussions/DiscussionGridView";
import { DiscussionListAssetHeader } from "@library/features/discussions/DiscussionListAssetHeader";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import QuickLinks from "@library/navigation/QuickLinks";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import keyBy from "lodash/keyBy";
import { GlobalPreset } from "@library/styles/globalStyleVars";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

export default {
    title: "Components/DiscussionLists",
};

setMeta("ui.useAdminCheckboxes", true);
setMeta("triage.enabled", true);

const loggedInStoreState = {
    users: {
        current: {
            data: STORY_ME_ADMIN,
        },
    },
    discussions: {
        discussionsByID: keyBy(DiscussionFixture.fakeDiscussions, "discussionID"),
    },
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
            discussionList: {
                announcementTag: {
                    bgColor: "rgb(0,155,10)",
                    fontColor: "rgb(0,255,255)",
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        return (
            <StoryContent>
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
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
            <QueryClientProvider client={queryClient}>
                <DiscussionsWidget
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{ discussionID: DiscussionFixture.fakeDiscussions[0].discussionID }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
                />
            </QueryClientProvider>
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
            <QueryClientProvider client={queryClient}>
                <DiscussionsWidget
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{ discussionID: DiscussionFixture.fakeDiscussions[0].discussionID }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
                />
            </QueryClientProvider>
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
            <QueryClientProvider client={queryClient}>
                <DiscussionsWidget
                    title="My Discussions"
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{ discussionID: DiscussionFixture.fakeDiscussions[0].discussionID }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.LINK }}
                />
            </QueryClientProvider>
        );
    },
);

export const FeaturedImage = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => (
        <PermissionsFixtures.AllPermissions>
            <StoryHeading>List View</StoryHeading>
            <QueryClientProvider client={queryClient}>
                <DiscussionsWidget
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{
                        discussionID: DiscussionFixture.fakeDiscussions[0].discussionID,
                        featuredImage: true,
                        fallbackImage: STORY_IMAGE,
                    }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.LIST }}
                />
                <StoryHeading>Grid View</StoryHeading>
                <DiscussionsWidget
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{
                        discussionID: DiscussionFixture.fakeDiscussions[0].discussionID,
                        featuredImage: true,
                        fallbackImage: STORY_IMAGE,
                    }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
                />
                <StoryHeading>Carousel View</StoryHeading>
                <DiscussionsWidget
                    discussions={DiscussionFixture.fakeDiscussions}
                    apiParams={{
                        discussionID: DiscussionFixture.fakeDiscussions[0].discussionID,
                        featuredImage: true,
                        fallbackImage: STORY_IMAGE,
                    }}
                    containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
                />
            </QueryClientProvider>
        </PermissionsFixtures.AllPermissions>
    ),
);

// Using a story only component for to use the mock data since the real DiscussionListAsset component does it's own fetching
const DiscussionListStory = (props) => {
    const [apiParams, setApiParams] = useState<IGetDiscussionListParams>({
        ...props.apiParams,
        discussionID: DiscussionFixture.fakeDiscussions[0].discussionID,
    });

    const mockProps = {
        ...props,
        apiParams,
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

    const updateApiParams = (newParams: Partial<IGetDiscussionListParams>) => {
        setApiParams({
            ...apiParams,
            ...newParams,
        });
    };

    const assetHeader = (
        <DiscussionListAssetHeader
            discussionIDs={DiscussionFixture.fakeDiscussions.map(({ discussionID }) => discussionID)}
            noCheckboxes={mockProps.noCheckboxes || isLink}
            paginationProps={{ totalResults: DiscussionFixture.fakeDiscussions.length }}
            apiParams={apiParams}
            updateApiParams={updateApiParams}
        />
    );
    const assetFooter = <NumberedPager totalResults={DiscussionFixture.fakeDiscussions.length} />;

    if (mockProps.isList) {
        return (
            <QueryClientProvider client={queryClient}>
                <HomeWidgetContainer
                    options={{
                        ...mockProps.containerOptions,
                        isGrid: false,
                    }}
                    extraHeader={assetHeader}
                >
                    <DiscussionListView
                        discussions={DiscussionFixture.fakeDiscussions}
                        discussionOptions={discussionOptions}
                    />

                    {assetFooter}
                </HomeWidgetContainer>
            </QueryClientProvider>
        );
    }

    if (isLink) {
        return (
            <QueryClientProvider client={queryClient}>
                <div>
                    <QuickLinks
                        links={DiscussionFixture.fakeDiscussions.map(({ name, url }, index) => ({
                            id: index.toString(),
                            name,
                            url,
                        }))}
                        containerOptions={mockProps.containerOptions}
                        extraHeader={assetHeader}
                    />
                    {assetFooter}
                </div>
            </QueryClientProvider>
        );
    }

    return (
        <QueryClientProvider client={queryClient}>
            <div>
                <DiscussionGridView
                    {...mockProps}
                    discussionOptions={discussionOptions}
                    discussions={DiscussionFixture.fakeDiscussions}
                    assetHeader={assetHeader}
                />
                {assetFooter}
            </div>
        </QueryClientProvider>
    );
};

export const AsAssetWithHeader = storyWithConfig(
    {
        themeVars: {},
        storeState: loggedInStoreState,
    },
    () => (
        <>
            <PermissionsFixtures.AllPermissions>
                <QueryClientProvider client={queryClient}>
                    <StoryHeading>ListView</StoryHeading>
                    <DiscussionListStory isList />
                    <StoryHeading>Grid View, with featured images and default fallback image</StoryHeading>
                    <DiscussionListStory
                        apiParams={{ featuredImage: true }}
                        containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
                    />
                    <StoryHeading>Grid View, no featured image, no checkboxes</StoryHeading>
                    <DiscussionListStory
                        containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
                        noCheckboxes
                    />
                    <StoryHeading>Carousel, with featured images and set fallback image</StoryHeading>
                    <DiscussionListStory
                        apiParams={{ featuredImage: true, fallbackImage: STORY_IMAGE }}
                        containerOptions={{ displayType: WidgetContainerDisplayType.CAROUSEL }}
                    />
                    <StoryHeading>Simple Links</StoryHeading>
                    <DiscussionListStory containerOptions={{ displayType: WidgetContainerDisplayType.LINK }} />
                </QueryClientProvider>
            </PermissionsFixtures.AllPermissions>
        </>
    ),
);

export const OnDarkThemeHiddenMeta = storyWithConfig(
    {
        themeVars: {
            discussionList: {
                contentBoxes: {
                    depth1: {
                        background: {
                            color: "#FFFFFF",
                        },
                        borderType: "none",
                    },
                    depth2: {
                        borderType: "border",
                    },
                },
                item: {
                    excerpt: {
                        display: false,
                    },
                    metas: {
                        display: {
                            startedByUser: false,
                            lastUser: false,
                            viewCount: false,
                        },
                    },
                },
            },
            global: {
                options: {
                    preset: GlobalPreset.DARK,
                },
            },
        },
        storeState: loggedInStoreState,
    },
    () => {
        setMeta("ui.useAdminCheckboxes", false);

        return (
            <StoryContent>
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </StoryContent>
        );
    },
);
