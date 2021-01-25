/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { Box } from "@library/layout/Box";

export default {
    title: "Components/Box",
    parameters: {},
};

export const WithBorder = storyWithConfig(
    {
        themeVars: {
            box: {
                options: {
                    borderType: "border",
                },
            },
        },
    },
    () => (
        <StoryContent>
            <StoryHeading depth={1}>Box</StoryHeading>
            <Box>
                <div>Box Content</div>
            </Box>
        </StoryContent>
    ),
);

export const WithShadowAndBackground = storyWithConfig(
    {
        themeVars: {
            box: {
                options: {
                    borderType: "shadow",
                    background: {
                        color: "#adadad",
                    },
                },
            },
        },
    },
    () => (
        <StoryContent>
            <StoryHeading depth={1}>Box</StoryHeading>
            <Box>
                <div>
                    <h2 style={{ color: "#2e2e2e" }}>Box With Shadow and Background</h2>
                </div>
            </Box>
        </StoryContent>
    ),
);

export const WithExtraPaddingAndBorder = storyWithConfig(
    {
        themeVars: {
            box: {
                options: {
                    borderType: "border",
                    background: {
                        color: "#d5cdfa",
                    },
                },
                border: {
                    color: "#2d00f7",
                    width: 3,
                },
                padding: {
                    top: 80,
                },
            },
        },
    },
    () => (
        <StoryContent>
            <Box>
                <div>
                    <h2 style={{ color: "#2d00f7" }}>Box With Padding and Border</h2>
                </div>
            </Box>
        </StoryContent>
    ),
);
