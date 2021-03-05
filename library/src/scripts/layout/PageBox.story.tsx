/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpers";

export default {
    title: "Components/PageBox",
    parameters: {},
};

export const WithBorder = () => (
    <StoryContent>
        <StoryHeading depth={1}>Box</StoryHeading>
        <PageBox
            options={{
                borderType: BorderType.BORDER,
            }}
        >
            <div>Box Content</div>
        </PageBox>
    </StoryContent>
);

export const WithShadowAndBackground = () => (
    <StoryContent>
        <StoryHeading depth={1}>Box</StoryHeading>
        <PageBox
            options={{
                borderType: BorderType.SHADOW,
                background: {
                    color: "#adadad",
                },
            }}
        >
            <div>
                <h2 style={{ color: "#2e2e2e" }}>Box With Shadow and Background</h2>
            </div>
        </PageBox>
    </StoryContent>
);

export const WithExtraPaddingAndBorder = () => (
    <StoryContent>
        <PageBox
            options={{
                borderType: BorderType.BORDER,
                background: {
                    color: "#d5cdfa",
                },
                border: {
                    color: "#2d00f7",
                    width: 3,
                },
                spacing: {
                    top: 80,
                },
            }}
        >
            <div>
                <h2 style={{ color: "#2d00f7" }}>Box With Padding and Border</h2>
            </div>
        </PageBox>
    </StoryContent>
);
