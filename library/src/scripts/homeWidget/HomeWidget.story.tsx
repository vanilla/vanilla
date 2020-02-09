/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT, STORY_IMAGE } from "@library/storybook/storyData";
import { HomeWidgetContainer, IHomeWidgetContainerProps } from "@library/homeWidget/HomeWidgetContainer";
import { style } from "typestyle";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { BorderType } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export default {
    title: "Home Widget",
};

export function ContainerTextOnly() {
    return (
        <div>
            <ContainerWithOptions title="4 columns" options={{ maxColumnCount: 4 }} />
            <ContainerWithOptions title="3 columns" options={{ maxColumnCount: 3 }} />
            <ContainerWithOptions title="2 columns" options={{ maxColumnCount: 2 }} />
            <ContainerWithOptions title="1 column" options={{ maxColumnCount: 1 }} />
        </div>
    );
}

export function ContainerWithImage() {
    return (
        <div>
            <ContainerWithOptionsAndImage title="4 columns" options={{ maxColumnCount: 4 }} />
            <ContainerWithOptionsAndImage title="3 columns" options={{ maxColumnCount: 3 }} />
            <ContainerWithOptionsAndImage title="2 columns" options={{ maxColumnCount: 2 }} />
        </div>
    );
}

ContainerWithImage.story = {
    parameters: {
        chromatic: {
            viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
        },
    },
};

export function Items() {
    return (
        <div>
            <StoryHeading>Title, Description</StoryHeading>
            <ItemIn4Variants>
                <HomeWidgetItem to="#" name="Hello Title" description={STORY_IPSUM_SHORT}></HomeWidgetItem>
            </ItemIn4Variants>
            <StoryHeading>Title, Description - Long</StoryHeading>
            <ItemIn4Variants>
                <HomeWidgetItem
                    to="#"
                    name="Hello Longer longer longer longer longer even longer"
                    description={STORY_IPSUM_MEDIUM}
                ></HomeWidgetItem>
            </ItemIn4Variants>
            <StoryHeading>Title, Description, Image</StoryHeading>
            <ItemIn4Variants>
                <HomeWidgetItem
                    to="#"
                    name="Hello Title with an Image"
                    description={STORY_IPSUM_MEDIUM}
                    imageUrl={STORY_IMAGE}
                    options={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE }}
                ></HomeWidgetItem>
            </ItemIn4Variants>
            <StoryHeading>Missing Image</StoryHeading>
            <ItemIn4Variants>
                <HomeWidgetItem
                    to="#"
                    name="Hello Title with a missing Image"
                    description={STORY_IPSUM_MEDIUM}
                    options={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE }}
                ></HomeWidgetItem>
            </ItemIn4Variants>
        </div>
    );
}

function ContainerWithOptionsAndImage(props: Omit<IHomeWidgetContainerProps, "children">) {
    return (
        <HomeWidgetContainer {...props}>
            <DummyItem image />
            <DummyItem image />
            <DummyItem image />
            <DummyItem image />
            <DummyItem image />
        </HomeWidgetContainer>
    );
}

function ContainerWithOptions(props: Omit<IHomeWidgetContainerProps, "children">) {
    return (
        <HomeWidgetContainer {...props}>
            <DummyItem />
            <DummyItem />
            <DummyItem />
            <DummyItem />
            <DummyItem />
        </HomeWidgetContainer>
    );
}

function DummyItem(props: { image?: boolean }) {
    return (
        <HomeWidgetItem
            options={{
                contentType: props.image
                    ? HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE
                    : HomeWidgetItemContentType.TITLE_DESCRIPTION,
            }}
            imageUrl={STORY_IMAGE}
            to="#"
            name="Hello Longer longer longer longer longer even longer"
            description={STORY_IPSUM_MEDIUM}
        ></HomeWidgetItem>
    );
}

function ItemIn4Variants(props: { children: React.ReactElement }) {
    const item1 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, borderType: BorderType.NONE },
    });
    const item2 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, borderType: BorderType.BORDER },
    });
    const item3 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, borderType: BorderType.SHADOW },
    });
    const item4 = React.cloneElement(props.children, {
        options: {
            ...props.children.props.options,
            borderType: BorderType.SHADOW,
            background: { image: "linear-gradient(215.7deg, #FCFEFF 16.08%, #fafdff 63.71%)" },
        },
    });
    const itemContainer = style({
        flex: 1,
        marginRight: 24,
        $nest: {
            "&:last-child": {
                marginRight: 0,
            },
        },
    });

    const root = style({
        display: "flex",
    });

    return (
        <div className={root}>
            <div className={itemContainer}>
                <StoryHeading>No border</StoryHeading>
                {item1}
            </div>
            <div className={itemContainer}>
                <StoryHeading>Border</StoryHeading>
                {item2}
            </div>
            <div className={itemContainer}>
                <StoryHeading>Shadow</StoryHeading>
                {item3}
            </div>
            <div className={itemContainer}>
                <StoryHeading>Custom BG</StoryHeading>
                {item4}
            </div>
        </div>
    );
}
