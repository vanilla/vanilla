/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { DiscussionListLoader } from "@library/features/discussions/DiscussionListLoader";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";

export default {
    title: "Loaders/DiscussionLists",
};

const ideaCounterOptions = {
    secondIcon: true,
    excerpt: true,
};

const iconInMetaOptions = {
    iconInMeta: true,
    excerpt: true,
};

const iconHiddenOptions = {
    icon: false,
    excerpt: true,
};

const featuredImageOptions = {
    image: true,
    checkbox: true,
    excerpt: true,
};

const maxWidth = 1000;

export const List = storyWithConfig({}, () => {
    const displayType = WidgetContainerDisplayType.LIST;
    const containerOptions = {
        maxWidth,
        displayType,
        borderType: BorderType.SEPARATOR,
        isGrid: false,
    };

    return (
        <>
            <HomeWidgetContainer title="Default" options={containerOptions}>
                <DiscussionListLoader />
            </HomeWidgetContainer>
            <HomeWidgetContainer title="Idea vote counter" options={containerOptions}>
                <DiscussionListLoader displayType={displayType} itemOptions={ideaCounterOptions} />
            </HomeWidgetContainer>
            <HomeWidgetContainer title="User Icon in Metas with 5 items" options={containerOptions}>
                <DiscussionListLoader displayType={displayType} count={5} itemOptions={iconInMetaOptions} />
            </HomeWidgetContainer>
            <HomeWidgetContainer title="Icon Hidden with 3 items" options={containerOptions}>
                <DiscussionListLoader displayType={displayType} count={3} itemOptions={iconHiddenOptions} />
            </HomeWidgetContainer>
            <HomeWidgetContainer title="Featured Images with Checkboxes" options={containerOptions}>
                <DiscussionListLoader displayType={displayType} itemOptions={featuredImageOptions} />
            </HomeWidgetContainer>
        </>
    );
});

export const Grid = storyWithConfig({}, () => {
    const displayType = WidgetContainerDisplayType.GRID;
    const containerOptions = {
        maxWidth,
        displayType,
    };

    return (
        <>
            <DiscussionListLoader
                containerProps={{
                    title: "Default",
                    options: containerOptions,
                }}
                displayType={displayType}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Idea vote counter",
                    options: containerOptions,
                }}
                displayType={displayType}
                itemOptions={ideaCounterOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "User Icon in Metas with 5 items",
                    options: containerOptions,
                }}
                displayType={displayType}
                count={5}
                itemOptions={iconInMetaOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Icon Hidden with 3 items",
                    options: containerOptions,
                }}
                displayType={displayType}
                count={3}
                itemOptions={iconHiddenOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Featured Images with Checkboxes",
                    options: containerOptions,
                }}
                displayType={displayType}
                itemOptions={featuredImageOptions}
            />
        </>
    );
});

export const Carousel = storyWithConfig({}, () => {
    const displayType = WidgetContainerDisplayType.CAROUSEL;
    const containerOptions = {
        maxWidth,
        displayType,
    };

    return (
        <>
            <DiscussionListLoader
                containerProps={{
                    title: "Default",
                    options: containerOptions,
                }}
                displayType={displayType}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Idea vote counter",
                    options: containerOptions,
                }}
                displayType={displayType}
                itemOptions={ideaCounterOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "User Icon in Metas with 5 items",
                    options: containerOptions,
                }}
                displayType={displayType}
                count={5}
                itemOptions={iconInMetaOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Icon Hidden with 3 items",
                    options: containerOptions,
                }}
                displayType={displayType}
                count={3}
                itemOptions={iconHiddenOptions}
            />
            <DiscussionListLoader
                containerProps={{
                    title: "Featured Images with Checkboxes",
                    options: containerOptions,
                }}
                displayType={displayType}
                itemOptions={featuredImageOptions}
            />
        </>
    );
});

export const Links = storyWithConfig({}, () => {
    const displayType = WidgetContainerDisplayType.LINK;

    return (
        <StoryContent>
            <StoryHeading>Links</StoryHeading>
            <DiscussionListLoader displayType={displayType} />
        </StoryContent>
    );
});
