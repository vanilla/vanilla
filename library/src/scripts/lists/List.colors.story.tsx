/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { List } from "@library/lists/List";
import { StoryListItems } from "@library/lists/ListItem.story";
import { StoryContent } from "@library/storybook/StoryContent";
import { IStoryTheme, storyWithConfig } from "@library/storybook/StoryContext";
import { GlobalPreset } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpers";
import React from "react";

export default {
    title: "Components/Lists/Color Variants",
};

export const Dark = storyWithConfig(
    {
        themeVars: {
            global: {
                options: {
                    preset: GlobalPreset.DARK,
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <List options={{ itemBox: { borderType: BorderType.BORDER } }}>
                    <StoryListItems />
                </List>
            </StoryContent>
        );
    },
);

const greishTheme: IStoryTheme = {
    global: {
        body: {
            backgroundImage: {
                color: "#fafafa",
            },
        },
    },
    listItem: {
        title: {
            font: {
                color: "#194378",
                weight: 600,
            },
            fontState: {
                textDecoration: "underline",
            },
        },
    },
};

export const GreyishListBg = storyWithConfig(
    {
        themeVars: greishTheme,
    },
    () => {
        return (
            <StoryContent>
                <List
                    options={{
                        box: {
                            borderType: BorderType.SHADOW,
                            background: {
                                color: "#fff",
                            },
                        },
                        itemBox: { borderType: BorderType.SEPARATOR },
                    }}
                >
                    <StoryListItems />
                </List>
            </StoryContent>
        );
    },
);

export const GreyishListItemBg = storyWithConfig(
    {
        themeVars: greishTheme,
    },
    () => {
        return (
            <StoryContent>
                <List
                    options={{
                        itemBox: {
                            borderType: BorderType.SHADOW,
                            background: {
                                color: "#fff",
                            },
                        },
                    }}
                >
                    <StoryListItems />
                </List>
            </StoryContent>
        );
    },
);
