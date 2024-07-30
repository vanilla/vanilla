/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { DotLoader } from "./DotLoader";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

export default {
    title: "Loaders/DotLoader",
};

export const LightBackground = storyWithConfig({}, () => {
    return (
        <StoryContent>
            <StoryHeading>Default Loader State</StoryHeading>
            <DotLoader />
            <StoryHeading>Dot Loader with Message</StoryHeading>
            <DotLoader message="This component is loading..." />
        </StoryContent>
    );
});

export const DarkBackground = storyWithConfig(
    {
        themeVars: {
            global: {
                mainColors: {
                    bg: "#303030",
                    fg: "#efefef",
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <StoryHeading>Default Loader State</StoryHeading>
                <DotLoader />
                <StoryHeading>Dot Loader with Message</StoryHeading>
                <DotLoader message="This component is loading..." />
            </StoryContent>
        );
    },
);

export const CustomTheme = storyWithConfig(
    {
        themeVars: {
            global: {
                mainColors: {
                    fg: "#123456",
                    bg: "#abcdef",
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <StoryParagraph>
                    The color of the dots is based on the defined <code>global.mainColors.bg</code> and{" "}
                    <code>global.mainColor.fg</code> in the theme variables.
                </StoryParagraph>
                <StoryHeading>Default Loader State</StoryHeading>
                <DotLoader />
                <StoryHeading>Dot Loader with Message</StoryHeading>
                <DotLoader message="This component is loading..." />
            </StoryContent>
        );
    },
);
