import React from "react";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { STORY_ICON, STORY_IMAGE, STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import PanelWidget from "@library/layout/components/PanelWidget";

export default {
    title: "Components/Carousel",
};

function StoryCarousel() {
    return (
        <HomeWidgetContainer
            title={"This is a Carousel"}
            subtitle={"This is a subtitle"}
            description={STORY_IPSUM_MEDIUM}
            options={{ isCarousel: true }}
        >
            <HomeWidgetItem
                to="#"
                name="Hello World 1"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
                imageUrl={STORY_IMAGE}
            />
            <HomeWidgetItem
                to="#"
                name="Hello World 2"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
                imageUrl={STORY_IMAGE}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 3"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 4"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 5"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 6"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 7"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
            <HomeWidgetItem
                imageUrl={STORY_IMAGE}
                to="#"
                name="Hello World 8"
                description={STORY_IPSUM_SHORT}
                iconUrl={STORY_ICON}
            />
        </HomeWidgetContainer>
    );
}

export const Simple = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        return <StoryCarousel />;
    },
);

export const InPanelLayout = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        return (
            <SectionTwoColumns
                mainBottom={
                    <PanelWidget>
                        <StoryCarousel />
                    </PanelWidget>
                }
                secondaryBottom={
                    <PanelWidget>
                        <StoryCarousel />
                    </PanelWidget>
                }
            ></SectionTwoColumns>
        );
    },
);

export const CustomGutterSizes = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                constants: {
                    fullGutter: 75,
                },
            },
        },
    },
    () => {
        return (
            <HomeWidgetContainer
                title={"This is a Carousel"}
                subtitle={"This is a subtitle"}
                description={STORY_IPSUM_MEDIUM}
                options={{ isCarousel: true }}
            >
                <HomeWidgetItem to="#" name="Hello World 1" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 2" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 3" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 4" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 5" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 6" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 7" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
                <HomeWidgetItem to="#" name="Hello World 8" description={STORY_IPSUM_SHORT} iconUrl={STORY_ICON} />
            </HomeWidgetContainer>
        );
    },
);
