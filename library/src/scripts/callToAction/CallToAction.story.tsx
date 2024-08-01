/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpers";
import { CallToAction } from "@library/callToAction/CallToAction";
import { DeepPartial } from "redux";
import { ICallToActionOptions } from "@library/callToAction/CallToAction.variables";
import { STORY_IMAGE, STORY_IPSUM_LONG, STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { ButtonTypes } from "../forms/buttonTypes";

export default {
    title: "Widgets/CallToAction",
    parameters: {},
};

function CallToActionInit(props: Partial<React.ComponentProps<typeof CallToAction>>) {
    const content = {
        title: props.title ?? "My Title",
        description: props?.hasOwnProperty("description") ? props?.description : "My Description",
        imageUrl: props?.hasOwnProperty("imageUrl")
            ? props?.imageUrl
            : "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
        textCTA: "Action",
        otherCTAs: props?.hasOwnProperty("otherCTAs") ? props.otherCTAs : undefined,
    };
    const options: DeepPartial<ICallToActionOptions> = {
        box: {
            borderType: BorderType.BORDER,
        },
        imagePlacement: "top",
        ...props?.options,
    };
    return <CallToAction to={"/somewhere"} {...content} options={options} />;
}

function ThreeBorderTypes(props: Partial<React.ComponentProps<typeof CallToAction>>) {
    return (
        <StoryContent>
            <StoryHeading>Border Type Shadow</StoryHeading>
            <CallToActionInit
                {...props}
                options={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            />
            <StoryHeading>Border Type Border</StoryHeading>
            <CallToActionInit
                {...props}
                options={{
                    box: {
                        borderType: BorderType.BORDER,
                    },
                }}
            />
            <StoryHeading>Border Type None</StoryHeading>
            <CallToActionInit
                {...props}
                options={{
                    box: {
                        borderType: BorderType.NONE,
                    },
                }}
            />
        </StoryContent>
    );
}

export const WithEverything = storyWithConfig({}, () => (
    <>
        <StoryHeading>No Image</StoryHeading>
        <ThreeBorderTypes imageUrl={undefined} />
        <StoryHeading>With Image</StoryHeading>
        <ThreeBorderTypes />
        <StoryHeading>No Description</StoryHeading>
        <ThreeBorderTypes description={undefined} />
        <StoryHeading>Large Description</StoryHeading>
        <ThreeBorderTypes description={STORY_IPSUM_LONG} />
        <StoryHeading>Large Title</StoryHeading>
        <ThreeBorderTypes title={STORY_IPSUM_LONG} />
        <StoryHeading>CTA Only</StoryHeading>
        <ThreeBorderTypes title={undefined} description={undefined} imageUrl={undefined} />
    </>
));

export const ImagePlacementLeft = storyWithConfig({}, () => (
    <>
        <StoryHeading>On Left Side</StoryHeading>
        <StoryContent>
            <StoryHeading>Short Description</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_SHORT}
                options={{
                    imagePlacement: "left",
                    box: {
                        borderType: BorderType.BORDER,
                        border: {
                            radius: 0,
                        },
                    },
                }}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>Border + Medium Description</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_MEDIUM}
                options={{
                    imagePlacement: "left",
                    box: {
                        borderType: BorderType.BORDER,
                        border: {
                            radius: 10,
                        },
                    },
                }}
            />
        </StoryContent>

        <StoryContent>
            <StoryHeading>Long Description</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_LONG}
                options={{
                    imagePlacement: "left",
                    box: {
                        borderType: BorderType.BORDER,
                        border: {
                            radius: 10,
                        },
                    },
                }}
            />
        </StoryContent>
    </>
));

export const MultipleLinks = storyWithConfig({}, () => (
    <>
        <StoryHeading>Multiple Links: Left Alignment</StoryHeading>
        <StoryContent>
            <StoryHeading>Image: Top</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY }}
                otherCTAs={Array(6).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>No Image: 2 CTAs</StoryHeading>
            <CallToActionInit
                imageUrl={undefined}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY }}
                otherCTAs={Array(1).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>Image: Left</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY, imagePlacement: "left" }}
                otherCTAs={Array(4).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryHeading>Multiple Links: Center Alignment</StoryHeading>
        <StoryContent>
            <StoryHeading>Image: Top</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY, alignment: "center" }}
                otherCTAs={Array(6).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>Image: Left</StoryHeading>
            <CallToActionInit
                imageUrl={STORY_IMAGE}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY, alignment: "center", imagePlacement: "left" }}
                otherCTAs={Array(4).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>No Image: 2 CTAs</StoryHeading>
            <CallToActionInit
                imageUrl={undefined}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY, alignment: "center" }}
                otherCTAs={Array(1).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
        <StoryContent>
            <StoryHeading>No Image: More than 2 CTAs</StoryHeading>
            <CallToActionInit
                imageUrl={undefined}
                description={STORY_IPSUM_SHORT}
                options={{ linkButtonType: ButtonTypes.PRIMARY, alignment: "center" }}
                otherCTAs={Array(4).fill({
                    to: "/another-link",
                    textCTA: "Another Action",
                    linkButtonType: ButtonTypes.STANDARD,
                })}
            />
        </StoryContent>
    </>
));
