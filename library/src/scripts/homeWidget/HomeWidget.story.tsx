/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { HomeWidgetItem, IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT, STORY_IMAGE } from "@library/storybook/storyData";
import { HomeWidgetContainer, IHomeWidgetContainerProps } from "@library/homeWidget/HomeWidgetContainer";
import { style } from "typestyle";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { BorderType } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { color } from "csx";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { ButtonTypes } from "@library/forms/buttonStyles";

export default {
    title: "Home Widget",
};

const STANDARD_5_ITEMS = [dummyItemProps(), dummyItemProps(), dummyItemProps(), dummyItemProps(), dummyItemProps()];

export function ContainerTextOnly() {
    return (
        <div>
            <HomeWidget title="4 columns" itemData={STANDARD_5_ITEMS} containerOptions={{ maxColumnCount: 4 }} />
            <HomeWidget title="3 columns" itemData={STANDARD_5_ITEMS} containerOptions={{ maxColumnCount: 3 }} />
            <HomeWidget title="2 columns" itemData={STANDARD_5_ITEMS} containerOptions={{ maxColumnCount: 2 }} />
            <HomeWidget title="1 column" itemData={STANDARD_5_ITEMS} containerOptions={{ maxColumnCount: 1 }} />
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

export function ContainerItemEdgeCases() {
    return (
        <div>
            <HomeWidgetContainer title="Text only, varying heights">
                <DummyItem />
                <DummyItem shortTitle />
                <DummyItem shortBody />
                <DummyItem shortTitle />
                <DummyItem />
            </HomeWidgetContainer>
            <HomeWidgetContainer title="Missing image, varying heights">
                <DummyItem image />
                <DummyItem image imageMissing />
                <DummyItem image shortBody />
                <DummyItem image shortTitle />
                <DummyItem image />
            </HomeWidgetContainer>
        </div>
    );
}

export function NotEnoughItems() {
    return (
        <div>
            <HomeWidget
                itemData={[dummyItemProps()]}
                title="Want 4, only 1 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
            />
            <HomeWidget
                itemData={[dummyItemProps(), dummyItemProps()]}
                title="Want 4, only 2 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
            />
            <HomeWidget
                itemData={[dummyItemProps(), dummyItemProps(), dummyItemProps()]}
                title="Want 4, only 3 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
            />
            <HomeWidget
                itemData={[dummyItemProps()]}
                title="Images - Want 4, only 1 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
            <HomeWidget
                itemData={[dummyItemProps(), dummyItemProps()]}
                title="Images - Want 4, only 2 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
            <HomeWidget
                itemData={[dummyItemProps(), dummyItemProps(), dummyItemProps()]}
                title="Images - Want 4, only 3 given"
                containerOptions={{
                    maxColumnCount: 4,
                    viewAll: { to: "#", position: "top" },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
        </div>
    );
}

(NotEnoughItems as any).story = {
    parameters: {
        chromatic: {
            viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
        },
    },
};

export const ContainerBackgroundVariants = storyWithConfig({ useWrappers: false }, () => {
    return (
        <div>
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="Solid Outer BG"
                containerOptions={{
                    maxColumnCount: 3,
                    outerBackground: { color: color("#EBF6FD") },
                    viewAll: { to: "#", position: "top" },
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                maxItemCount={4}
                title="Outer BG w/ shadowed items"
                containerOptions={{
                    maxColumnCount: 2,
                    outerBackground: {
                        image:
                            "linear-gradient(0deg, rgba(181,219,255,1) 0%, rgba(223,246,255,1) 37%, rgba(255,255,255,1) 100%)",
                    },
                    viewAll: { to: "#", displayType: ButtonTypes.TRANSPARENT },
                }}
                itemOptions={{ borderType: BorderType.SHADOW }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                maxItemCount={4}
                title="Inner BG & shadow"
                containerOptions={{
                    maxColumnCount: 1,
                    outerBackground: { image: "linear-gradient(215.7deg, #FAFEFF 16.08%, #f6fdff 63.71%)" },
                    borderType: BorderType.SHADOW,
                    viewAll: { to: "#", displayType: ButtonTypes.PRIMARY },
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                maxItemCount={4}
                title="Very Very Very Very Long Title with a top view all button, Very Very Very long"
                containerOptions={{ maxColumnCount: 2, viewAll: { to: "#", position: "top" } }}
            />
        </div>
    );
});

(ContainerBackgroundVariants as any).story = {
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

interface IDummyItemProps {
    image?: boolean;
    imageMissing?: boolean;
    shortTitle?: boolean;
    shortBody?: boolean;
}

function dummyItemProps(props?: IDummyItemProps): IHomeWidgetItemProps {
    return {
        options: {
            contentType: props?.image
                ? HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE
                : HomeWidgetItemContentType.TITLE_DESCRIPTION,
        },
        imageUrl: props?.imageMissing ? undefined : STORY_IMAGE,
        to: "#",
        name: props?.shortTitle ? "Short Title" : "Hello Longer longer longer longer longer even longer",
        description: props?.shortBody ? STORY_IPSUM_SHORT : STORY_IPSUM_MEDIUM,
    };
}

function DummyItem(props: IDummyItemProps) {
    return <HomeWidgetItem {...dummyItemProps(props)}></HomeWidgetItem>;
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
        display: "flex",
        flexDirection: "column",
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
