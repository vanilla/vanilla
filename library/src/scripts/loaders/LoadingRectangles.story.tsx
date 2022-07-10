/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { codeBlockCSS } from "@rich-editor/quill/components/codeBlockStyles";

export default {
    title: "Loaders/LoadingRectangle",
};

function LoadingRectangleStory() {
    const codeBlock = codeBlockCSS();
    return (
        <StoryContent>
            <StoryHeading>Loaders</StoryHeading>
            <StoryParagraph className="userContent">
                {`Loaders default to 100% width and must take some pixel height. Don't forget to add `}
                <code className={"code code-inline"}>
                    {`<ScreenReaderContent` + `>{t("Loading")}<` + `/ScreenReaderContent>`}
                </code>
                {` once.`}
            </StoryParagraph>
            <div>
                <LoadingRectangle height={50} />
                <LoadingSpacer height={20} />
                <LoadingRectangle height={14} width={"95%"} />
                <LoadingSpacer height={12} />
                <LoadingRectangle height={14} width={"80%"} />
                <LoadingSpacer height={12} />
                <LoadingRectangle height={14} width={"82%"} />
                <LoadingSpacer height={12} />
                <LoadingRectangle height={14} width={"75%"} />
                <LoadingSpacer height={12} />
                <LoadingRectangle height={14} width={"85%"} />
            </div>
            <StoryHeading>Grid</StoryHeading>
            <StoryParagraph>The can be shaped hover you want</StoryParagraph>
            <div>
                <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
                    <LoadingRectangle style={{ borderRadius: 100, overflow: "hidden" }} height={100} width={100} />
                    <LoadingRectangle style={{ borderRadius: 12, overflow: "hidden" }} height={100} width={100} />
                    <LoadingRectangle style={{ borderRadius: 0, overflow: "hidden" }} height={100} width={100} />
                </div>
                <LoadingRectangle style={{ margin: "24px auto" }} height={100} width={"50%"} />
            </div>
        </StoryContent>
    );
}

export const Light = LoadingRectangleStory;
export const Dark = storyWithConfig(
    {
        themeVars: {
            global: {
                mainColors: {
                    bg: "#333",
                    fg: "#fff",
                },
            },
        },
    },
    LoadingRectangleStory,
);
