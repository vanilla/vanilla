/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import * as EditorIcons from "@library/icons/editorIcons";
import * as TitleBarIcons from "@library/icons/titleBar";
import * as CommonIcons from "@library/icons/common";
import * as EmojiGroupIcons from "@library/icons/emojiGroups";
import * as FileTypeIcons from "@library/icons/fileTypes";
import * as RevisionIcons from "@library/icons/revision";
import * as SearchIcons from "@library/icons/searchIcons";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { color } from "csx";

const story = storiesOf("Components", module);

const invert = ["NewPostMenuIcon"];

function IconSet({ icons }) {
    return (
        <StoryTiles>
            {Object.entries(icons)
                .filter(([name]) => name.endsWith("Icon") || name.endsWith("Logo"))
                .map(([name, Icon]: [string, any]) => (
                    <StoryTileAndTextCompact
                        key={name}
                        text={name}
                        backgroundColor={invert.includes(name) ? color("#555") : undefined}
                    >
                        <Icon />
                    </StoryTileAndTextCompact>
                ))}
        </StoryTiles>
    );
}

story.add("Icons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Global Icons</StoryHeading>
            <StoryParagraph>
                Use a function to call these icons. Note also that some of these functions have &quot;compact&quot;
                versions, which just means they have less padding for tighter spaces. Most icons render in a box 24px by
                24px.
            </StoryParagraph>

            <StoryHeading>Common</StoryHeading>
            <IconSet icons={CommonIcons} />
            <StoryHeading>Editor</StoryHeading>
            <IconSet icons={EditorIcons} />
            <StoryHeading>Emoji Groups</StoryHeading>
            <IconSet icons={EmojiGroupIcons} />
            <StoryHeading>File Types</StoryHeading>
            <IconSet icons={FileTypeIcons} />
            <StoryHeading>Title Bar</StoryHeading>
            <IconSet icons={TitleBarIcons} />
            <StoryHeading>Revisions</StoryHeading>
            <IconSet icons={RevisionIcons} />
            <StoryHeading>Search Icons</StoryHeading>
            <IconSet icons={SearchIcons} />
        </StoryContent>
    );
});
