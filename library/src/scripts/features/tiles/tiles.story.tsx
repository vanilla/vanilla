/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import Tiles, { TileAlignment } from "./Tiles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import Container from "@library/layout/components/Container";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Widgets",
    params: {
        chromatic: {
            viewports: [1400],
        },
    },
};

function TilesStory(props: { title: string }) {
    return (
        <>
            <StoryContent>
                <StoryHeading>{props.title}</StoryHeading>
            </StoryContent>
            <Tiles
                items={[
                    {
                        name: "Development",
                        description: "Processes and guidance for developers.",
                        url: "https://staff.vanillaforums.com/kb/dev",
                        icon: "https://us.v-cdn.net/5022541/uploads/341/G35SLM2LBY4G.png",
                    },
                    {
                        name: "Success",
                        description: "Information for CSMs about troubleshooting & working with Vanilla.",
                        url: "https://staff.vanillaforums.com/kb/success",
                        icon: "https://us.v-cdn.net/5022541/uploads/466/WCXDHD4UMW3K.png",
                    },
                    {
                        name: "Internal Testing",
                        description: "Knowledge for us in internal tests. Don't put anything important here.",
                        url: "https://staff.vanillaforums.com/kb/testing",
                        icon: "https://us.v-cdn.net/5022541/uploads/048/66SQHHGSZT2R.png",
                    },
                    {
                        name: "Information Security",
                        description: "Internal company security practices.",
                        url: "https://staff.vanillaforums.com/kb/infosec",
                        icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                    },
                    {
                        name: "Information Security",
                        description: "Internal company security practices.",
                        url: "https://staff.vanillaforums.com/kb/infosec",
                        icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                    },
                ]}
                title={"Our Games"}
                emptyMessage={"No subcommunities found"}
            />
        </>
    );
}

export const Tiles2Columns = storyWithConfig({}, () => {
    return <TilesStory title="As Tiles - 2 columns" />;
});

export const Tiles3Columns = storyWithConfig(
    {
        themeVars: {
            tiles: {
                options: {
                    columns: 3,
                },
            },
        },
    },
    () => {
        return <TilesStory title="As Tiles - 3 columns" />;
    },
);

export const Tiles4Columns = storyWithConfig(
    {
        themeVars: {
            tiles: {
                options: {
                    columns: 4,
                },
            },
        },
    },
    () => {
        return <TilesStory title="As Tiles - 4 columns" />;
    },
);

export const Tiles4ColumnsLeftAligned = storyWithConfig(
    {
        themeVars: {
            tiles: {
                options: {
                    columns: 4,
                    alignment: TileAlignment.LEFT,
                },
            },
        },
    },
    () => {
        return <TilesStory title="As Tiles - 4 columns - Left Aligned" />;
    },
);

export const Tiles4Variation1 = storyWithConfig(
    {
        themeVars: {
            tiles: {
                options: {
                    columns: 4,
                    alignment: TileAlignment.LEFT,
                },
                sizing: {
                    containerWidthFourColumns: 1275,
                },
            },
            tile: {
                options: {
                    alignment: TileAlignment.LEFT,
                },
                frame: {
                    height: 32,
                    width: "auto",
                    marginBottom: 24,
                },
                link: {
                    borderRadius: 8,
                    bgImage: "#FFFFFF",
                    bgImageHover: "linear-gradient(248.5deg, #FCFEFF 16.08%, #F5FBFF 63.71%), #FFFFFF",
                },
                title: {
                    marginBottom: 12,
                },
            },
            shadow: {
                widget: {
                    horizontalOffset: 0,
                    verticalOffset: 2,
                    blur: 6,
                    opacity: 0.12,
                },
                widgetHover: {
                    horizontalOffset: 0,
                    verticalOffset: 40,
                    blur: 80,
                    opacity: 0.12,
                },
            },
        },
    },
    () => {
        return <TilesStory title="As Tiles - Variation 1" />;
    },
);
