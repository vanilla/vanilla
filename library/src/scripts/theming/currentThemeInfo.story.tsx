/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { IThemePreview, ThemeType } from "@library/theming/themeReducer";
import { storiesOf } from "@storybook/react";
import React from "react";
import CurrentThemeInfo from "./CurrentThemeInfo";
import ThemePreviewCard from "./ThemePreviewCard";

const story = storiesOf("Theme UI", module);

const preview: IThemePreview = {
    variables: {
        globalBg: "#fff",
        globalPrimary: "#985E6D",
        globalFg: "#555a62",
        titleBarBg: "#0291db",
        titleBarFg: "#fff",
    },
    info: {
        Description: {
            type: "string",
            value:
                "A responsive Vanilla theme with customization options. A responsive Vanilla theme with customization options.",
        },
        Authors: { type: "string", value: "Author1, Author2, Author3" },
    },
    imageUrl: null,
};

story.add("Current Theme", () => {
    return (
        <>
            <StoryHeading depth={1}>Current Theme</StoryHeading>

            <div style={{ display: "flex" }}>
                <div style={{ width: 400 }}>
                    <ThemePreviewCard preview={preview} isActiveTheme={true} />
                </div>
                <CurrentThemeInfo
                    theme={{
                        name: "Keystone",
                        supportedSections: ["Forum", "Knowledge Base", "HomePage"],
                        themeID: "keystone",
                        revisionID: null,
                        assets: {},
                        features: {},
                        preview: preview,
                        type: ThemeType.FS,
                        current: true,
                        version: "4.0.0",
                    }}
                />
            </div>
        </>
    );
});
