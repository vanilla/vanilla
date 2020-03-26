/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import CurrentThemeInfo from "./CurrentThemeInfo";
import ThemePreviewCard from "./ThemePreviewCard";
import { StoryTile } from "@library/storybook/StoryTile";
import { ThemeType } from "@library/theming/themeReducer";

const story = storiesOf("Theme", module);

story.add("Current Theme", () => {
    return (
        <>
            <StoryHeading depth={1}>Current Theme</StoryHeading>

            <div style={{ display: "flex" }}>
                <div style={{ width: 400 }}>
                    <ThemePreviewCard
                        globalBg={"#fff"}
                        globalPrimary={"#985E6D"}
                        globalFg={"#555a62"}
                        titleBarBg={"#0291db"}
                        titleBarFg={"#fff"}
                        isActiveTheme={true}
                    />
                </div>
                <CurrentThemeInfo
                    theme={{
                        name: "Keystone",
                        supportedSections: ["Forum", "Knowledge Base", "HomePage"],
                        themeID: "keystone",
                        assets: {},
                        features: {},
                        preview: {
                            info: {
                                Description: {
                                    type: "string",
                                    info:
                                        "A responsive Vanilla theme with customization options. A responsive Vanilla theme with customization options.",
                                },
                                Authors: { type: "string", info: "Author1, Author2, Author3" },
                            },
                        },
                        type: ThemeType.FS,
                        current: true,
                        version: "4.0.0",
                    }}
                />
            </div>
        </>
    );
});
