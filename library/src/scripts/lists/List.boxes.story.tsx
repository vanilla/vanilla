/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { List } from "@library/lists/List";
import { StoryListItems } from "@library/lists/ListItem.story";
import { StoryContent } from "@library/storybook/StoryContent";
import { BorderType } from "@library/styles/styleHelpers";
import React from "react";

export default {
    title: "Components/Lists/Box Variants",
};

export function NoneAndBorder() {
    return (
        <StoryContent>
            <List options={{ itemBox: { borderType: BorderType.BORDER } }}>
                <StoryListItems />
            </List>
        </StoryContent>
    );
}

export function NoneAndShadow() {
    return (
        <StoryContent>
            <List options={{ itemBox: { borderType: BorderType.SHADOW } }}>
                <StoryListItems />
            </List>
        </StoryContent>
    );
}

export function ShadowAndSeparator() {
    return (
        <StoryContent>
            <List
                options={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                    itemBox: { borderType: BorderType.SEPARATOR },
                }}
            >
                <StoryListItems />
            </List>
        </StoryContent>
    );
}

export function BorderAndSeparator() {
    return (
        <StoryContent>
            <List
                options={{
                    box: {
                        borderType: BorderType.BORDER,
                        border: {
                            width: 2,
                        },
                    },
                    itemBox: { borderType: BorderType.SEPARATOR },
                }}
            >
                <StoryListItems />
            </List>
        </StoryContent>
    );
}
