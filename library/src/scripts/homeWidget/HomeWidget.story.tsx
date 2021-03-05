/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { HomeWidgetItem, IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { STORY_ICON, STORY_IMAGE, STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { HomeWidgetContainer, IHomeWidgetContainerProps } from "@library/homeWidget/HomeWidgetContainer";
import { style } from "@library/styles/styleShim";
import { HomeWidgetItemContentType, IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { BorderType } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { color } from "csx";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { StandardNavLinksStory } from "@library/navigation/navLinksWithHeadings.story";
import { DeepPartial } from "redux";
import Container from "@library/layout/components/Container";

export default {
    title: "Widgets/Home Widget",
};

const STANDARD_5_ITEMS = [dummyItemProps(), dummyItemProps(), dummyItemProps(), dummyItemProps(), dummyItemProps()];
const iconUrls = {
    firstIcon: "https://us.v-cdn.net/5022541/uploads/341/G35SLM2LBY4G.png",
    secondIcon: "https://us.v-cdn.net/5022541/uploads/466/WCXDHD4UMW3K.png",
    thirdIcon: "https://us.v-cdn.net/5022541/uploads/048/66SQHHGSZT2R.png",
    forthIcon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
};

export function ContentTypeChatBubble() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_CHAT_BUBBLE,
    };
    return (
        <div>
            <HomeWidget
                title="4 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
            />
            <HomeWidget
                title="3 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 3 }}
            />
            <HomeWidget
                title="2 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 2 }}
            />
            <HomeWidget
                title="1 column"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 1 }}
            />
        </div>
    );
}

export function ContentTypeText() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
    };
    return (
        <div>
            <HomeWidget
                title="4 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
            />
            <HomeWidget
                title="3 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 3 }}
            />
            <HomeWidget
                title="2 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 2 }}
            />
            <HomeWidget
                title="1 column"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 1 }}
            />
        </div>
    );
}

export function ContentTypeImage() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
    };
    return (
        <div>
            <HomeWidget
                title="Bottom Aligned w/ Meta"
                itemOptions={{ ...itemOptions, verticalAlignment: "bottom" }}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
            />
            <HomeWidget
                title="4 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
            />
            <HomeWidget
                title="3 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 3 }}
            />
            <HomeWidget
                title="2 columns"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 2 }}
            />
            <HomeWidget
                title="1 column"
                itemOptions={itemOptions}
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 1 }}
            />
        </div>
    );
}

export function ContentTypeIcon() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
    };

    return (
        <div>
            <HomeWidget
                title="4 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="3 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 3 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="2 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 2 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="1 column with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 1 }}
                itemOptions={itemOptions}
            />
        </div>
    );
}

export function ContentTypeBackground() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_BACKGROUND,
    };

    return (
        <div>
            <HomeWidget
                title="4 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="3 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 3 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="2 columns with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 2 }}
                itemOptions={itemOptions}
            />
            <HomeWidget
                title="1 column with icon"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 1 }}
                itemOptions={itemOptions}
            />
        </div>
    );
}

export function NoMetas() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        display: { counts: false },
    };

    return (
        <div>
            <HomeWidget
                title="No Metas with Background"
                itemData={STANDARD_5_ITEMS}
                itemOptions={{
                    ...itemOptions,
                    contentType: HomeWidgetItemContentType.TITLE_BACKGROUND,
                }}
            />
            <HomeWidget
                title="No Metas with Icon"
                itemData={STANDARD_5_ITEMS}
                itemOptions={{ ...itemOptions, contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON }}
            />
            <HomeWidget
                title="No Metas with Image"
                itemData={STANDARD_5_ITEMS}
                itemOptions={{
                    ...itemOptions,
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
            <HomeWidget
                title="No Metas with Text"
                itemData={STANDARD_5_ITEMS}
                itemOptions={{
                    ...itemOptions,
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
            />
        </div>
    );
}

export function IconPlacementLeft() {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        imagePlacement: "left",
    };

    return (
        <div>
            <HomeWidget
                title="Icon Alignment Left with short name, no description, no border, no meta"
                itemData={[
                    dummyItemProps({ iconCustomUrl: iconUrls.firstIcon, shortTitle: true }),
                    dummyItemProps({ iconCustomUrl: iconUrls.secondIcon, shortTitle: true }),
                    dummyItemProps({ iconCustomUrl: iconUrls.thirdIcon, shortTitle: true }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon, shortTitle: true }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon, shortTitle: true }),
                ]}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false, description: false },
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Icon Alignment Left with long name, no description, no border, no meta"
                itemData={[
                    dummyItemProps({ iconCustomUrl: iconUrls.firstIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.secondIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.thirdIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                ]}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false, description: false },
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Icon Alignment Left with short name, description and icon background, no meta and no border"
                itemData={[
                    dummyItemProps({
                        iconCustomUrl: iconUrls.firstIcon,
                        shortTitle: true,
                        shortBody: true,
                    }),
                    dummyItemProps({
                        iconCustomUrl: iconUrls.secondIcon,
                        shortTitle: true,
                        shortBody: true,
                    }),
                    dummyItemProps({
                        iconCustomUrl: iconUrls.thirdIcon,
                        shortTitle: true,
                        shortBody: true,
                    }),
                    dummyItemProps({
                        iconCustomUrl: iconUrls.forthIcon,
                        shortTitle: true,
                        shortBody: true,
                    }),
                    dummyItemProps({
                        iconCustomUrl: iconUrls.forthIcon,
                        shortTitle: true,
                        shortBody: true,
                    }),
                ]}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false },
                    imagePlacement: "left",
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Icon Alignment Left with long name and long description, no border, no meta"
                itemData={[
                    dummyItemProps({ iconCustomUrl: iconUrls.firstIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.secondIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.thirdIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                ]}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false },
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Icon Alignment Left with long name and long description, with border, no meta"
                itemData={[
                    dummyItemProps({ iconCustomUrl: iconUrls.firstIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.secondIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.thirdIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                ]}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false },
                }}
            />
            <HomeWidget
                title="Icon Alignment Left with long name and long description, with border, with meta"
                itemData={[
                    dummyItemProps({ iconCustomUrl: iconUrls.firstIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.secondIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.thirdIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                    dummyItemProps({ iconCustomUrl: iconUrls.forthIcon }),
                ]}
                itemOptions={{
                    ...itemOptions,
                }}
            />
        </div>
    );
}

export const ImagePlacementLeft = storyWithConfig({ useWrappers: false }, () => {
    const itemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        imagePlacement: "left",
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
    };

    const mobileItemOptions: DeepPartial<IHomeWidgetItemOptions> = {
        imagePlacementMobile: "left",
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
    };

    const items = [
        dummyItemProps({ image: true, shortTitle: true }),
        dummyItemProps({ image: true, shortTitle: true }),
        dummyItemProps({ image: true, shortTitle: true }),
        dummyItemProps({ image: true, shortTitle: true }),
        dummyItemProps({ image: true, shortTitle: true }),
    ];

    return (
        <div>
            <HomeWidget
                title="Image Alignment Left with short name, no border, no meta"
                itemData={items}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false },
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Image Alignment Left with short name, no meta"
                itemData={items}
                itemOptions={{
                    ...itemOptions,
                    display: { counts: false },
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            />
            <HomeWidget
                title="Image Alignment Left with metas"
                itemData={items}
                itemOptions={{
                    ...itemOptions,
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            />
            <HomeWidget
                title="Image Alignment Mobile Only Left with short name, no border"
                itemData={items}
                itemOptions={{
                    ...mobileItemOptions,
                    display: { counts: false },
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
            <HomeWidget
                title="Image Alignment Left only on mobile"
                itemData={items}
                itemOptions={{
                    ...mobileItemOptions,
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            />
        </div>
    );
});

ImagePlacementLeft.parameters = {
    chromatic: {
        viewports: [1200, 400],
    },
};

export function ContentTypeVariants() {
    const itemOptionsVariantA: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
        box: {
            borderType: BorderType.SHADOW,
            border: {
                radius: 2,
            },
        },
        display: { description: false },
    };

    const itemOptionsVariantB: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
        box: {
            border: {
                radius: 2,
            },
        },
        alignment: "left",
        display: { counts: true },
    };

    const itemOptionsVariantC: DeepPartial<IHomeWidgetItemOptions> = {
        contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
        box: {
            border: {
                radius: 2,
            },
        },
    };

    const itemOptionsVariantD: DeepPartial<IHomeWidgetItemOptions> = {
        alignment: "left",
        verticalAlignment: "middle",
        box: {
            border: {
                radius: 2,
            },
        },
    };

    return (
        <div>
            <HomeWidget
                title="4 columns with icon & title"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={itemOptionsVariantA}
            />
            <HomeWidget
                title="4 columns with image, title, description & metas"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={itemOptionsVariantB}
            />
            <HomeWidget
                title="4 columns with icon, title & description"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={itemOptionsVariantC}
            />
            <HomeWidget
                title="4 columns with image, title & button"
                itemData={STANDARD_5_ITEMS}
                containerOptions={{ maxColumnCount: 4 }}
                itemOptions={{
                    ...itemOptionsVariantD,
                    contentType: HomeWidgetItemContentType.TITLE_BACKGROUND,
                }}
            />
        </div>
    );
}

export function ContainerHeadingVariants() {
    return (
        <>
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="Heading, with overline and uppercase subtitle, having some letter spacing"
                containerOptions={{
                    maxColumnCount: 4,
                    headerAlignment: "center",
                    description:
                        "And some long description with some dummy text, and some description with some dummy text, and some description with some dummy text , and some description with some dummy text",
                    subtitle: {
                        type: "overline",
                        content: "About the company",
                    },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="Heading, Center Aligned, with title, overline subtitle and description"
                containerOptions={{
                    maxColumnCount: 4,
                    headerAlignment: "center",
                    description:
                        "And some long description with some dummy text, and some description with some dummy text, and some description with some dummy text , and some description with some dummy text",
                    subtitle: {
                        type: "overline",
                        content: "About the company",
                        font: {
                            transform: "none",
                        },
                    },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="Heading, Center Aligned, with title, subtitle, viewAll button"
                containerOptions={{
                    maxColumnCount: 4,
                    headerAlignment: "center",
                    viewAll: { to: "#", position: "top" },
                    description:
                        "And some long description with some dummy text, and some description with some dummy text, and some description with some dummy text , and some description with some dummy text",
                    subtitle: {
                        type: "standard",
                        content: "And some subtitle with some dummy text",
                    },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
            />
        </>
    );
}

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

export const NestedContainers = storyWithConfig({ useWrappers: false }, () => {
    return (
        <Container fullGutter>
            <div style={{ background: "#eee", padding: 24, marginBottom: 24 }}>
                <h2>Something in the container</h2>
                <p>Hello world</p>
            </div>
            <HomeWidgetContainer options={{ noGutter: true, maxColumnCount: 4 }} title="Missing image, varying heights">
                <DummyItem image />
                <DummyItem image imageMissing />
                <DummyItem image shortBody />
                <DummyItem image shortTitle />
                <DummyItem image />
            </HomeWidgetContainer>
        </Container>
    );
});

NestedContainers.parameters = {
    chromatic: {
        viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
    },
};
export function NotEnoughItems() {
    function DifferentCounts(props: { options: DeepPartial<IHomeWidgetItemOptions>; prefix: string }) {
        return (
            <>
                <HomeWidget
                    itemData={[dummyItemProps()]}
                    title={`${props.prefix} - Want 4, only 1 given`}
                    itemOptions={props.options}
                    containerOptions={{
                        maxColumnCount: 4,
                        viewAll: { to: "#", position: "top" },
                    }}
                />
                <HomeWidget
                    itemData={[dummyItemProps(), dummyItemProps()]}
                    title={`${props.prefix} - Want 4, only 2 given`}
                    itemOptions={props.options}
                    containerOptions={{
                        maxColumnCount: 4,
                        viewAll: { to: "#", position: "top" },
                    }}
                />
                <HomeWidget
                    itemData={[dummyItemProps(), dummyItemProps(), dummyItemProps()]}
                    title={`${props.prefix} - Want 4, only 3 given`}
                    itemOptions={props.options}
                    containerOptions={{
                        maxColumnCount: 4,
                        viewAll: { to: "#", position: "top" },
                    }}
                />
            </>
        );
    }

    return (
        <div>
            <DifferentCounts prefix={"Text"} options={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION }} />
            <DifferentCounts
                prefix={"Images"}
                options={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE }}
            />
            <DifferentCounts
                prefix={"Background"}
                options={{ contentType: HomeWidgetItemContentType.TITLE_BACKGROUND }}
            />
            <DifferentCounts
                prefix={"Icon"}
                options={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON }}
            />
        </div>
    );
}

NotEnoughItems.parameters = {
    chromatic: {
        viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
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
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
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
                itemOptions={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
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
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                maxItemCount={4}
                title="Very Very Very Very Long Title with a top view all button, Very Very Very long"
                containerOptions={{ maxColumnCount: 2, viewAll: { to: "#", position: "top" } }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
            />
        </div>
    );
});

ContainerBackgroundVariants.parameters = {
    chromatic: {
        viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
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
            <StoryHeading>Active States</StoryHeading>
            <ItemIn4Variants>
                <HomeWidgetItem
                    to="#"
                    className={"focus-visible"}
                    name="Hello Title with a missing Image"
                    description={STORY_IPSUM_MEDIUM}
                ></HomeWidgetItem>
            </ItemIn4Variants>
        </div>
    );
}

export const AsNavLinks = storyWithConfig({ useWrappers: false }, () => {
    return (
        <div>
            <StandardNavLinksStory />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="As Navigation Links"
                maxItemCount={4}
                containerOptions={{
                    borderType: "navLinks",
                    maxColumnCount: 1,
                    viewAll: { to: "#", position: "bottom" },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
            />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="As Navigation Links"
                maxItemCount={4}
                containerOptions={{
                    borderType: "navLinks",
                    maxColumnCount: 2,
                    viewAll: { to: "#", position: "bottom" },
                }}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION,
                }}
            />
        </div>
    );
});

export const AsNavLinksFullBg = storyWithConfig({ useWrappers: false }, () => {
    return (
        <div>
            <StandardNavLinksStory />
            <HomeWidget
                itemData={STANDARD_5_ITEMS}
                title="After Navigation Links"
                maxItemCount={3}
                itemOptions={{
                    contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                }}
                containerOptions={{
                    viewAll: { to: "#" },
                    outerBackground: {
                        color: color("#F8F8F8"),
                    },
                }}
            />
        </div>
    );
});

AsNavLinks.parameters = {
    chromatic: {
        viewports: [500, 1200],
    },
};

interface IDummyItemProps {
    image?: boolean;
    icon?: boolean;
    iconCustomUrl?: string;
    imageMissing?: boolean;
    shortTitle?: boolean;
    shortBody?: boolean;
    noBody?: boolean;
    noCounts?: boolean;
    noBorder?: boolean;
    iconBackground?: boolean;
    callToAction?: string;
}

function dummyItemProps(props?: IDummyItemProps): IHomeWidgetItemProps {
    return {
        options: {
            contentType: props?.image
                ? HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE
                : props?.icon
                ? HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON
                : HomeWidgetItemContentType.TITLE_DESCRIPTION,

            box: {
                borderType: props?.noBorder ? BorderType.NONE : undefined,
            },
        },
        callToAction: "Learn more",
        url: "https://vanillaforums.com/en/",
        imageUrl: props?.imageMissing ? undefined : STORY_IMAGE,
        iconUrl: props?.imageMissing ? undefined : props?.iconCustomUrl ? props.iconCustomUrl : STORY_ICON,
        to: "#",
        name: props?.shortTitle ? "Short Title" : "Hello Longer longer longer longer longer even longer",
        description: props?.noBody ? undefined : props?.shortBody ? STORY_IPSUM_SHORT : STORY_IPSUM_MEDIUM,
        counts: props?.noCounts
            ? undefined
            : [
                  { count: 2000, labelCode: "Items" },
                  { count: 42, labelCode: "Resources" },
              ],
    };
}

function DummyItem(props: IDummyItemProps) {
    return <HomeWidgetItem {...dummyItemProps(props)}></HomeWidgetItem>;
}

function ItemIn4Variants(props: { children: React.ReactElement }) {
    const item1 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, box: { borderType: BorderType.NONE } },
    });
    const item2 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, box: { borderType: BorderType.BORDER } },
    });
    const item3 = React.cloneElement(props.children, {
        options: { ...props.children.props.options, box: { borderType: BorderType.SHADOW } },
    });
    const item4 = React.cloneElement(props.children, {
        options: {
            ...props.children.props.options,
            box: {
                borderType: BorderType.SHADOW,
                background: { image: "linear-gradient(215.7deg, #FCFEFF 16.08%, #f5fdff 63.71%)" },
            },
        },
    });
    const itemContainer = style({
        flex: 1,
        display: "flex",
        flexDirection: "column",
        marginRight: 24,
        ...{
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
