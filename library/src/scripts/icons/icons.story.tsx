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
import { Icon, iconRegistry } from "@vanilla/icons";
import { css } from "@emotion/css";
import groupBy from "lodash/groupBy";
import { labelize } from "@vanilla/utils";
import { IconType } from "@vanilla/icons";

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

const vanillaIconsStyle = css({
    display: "flex",
    maxHeight: 1200, // Adjust this to fit more icons.
    flexFlow: "column wrap",
    width: "calc(100% + 240px)",
    transform: "translateX(-105px)",

    h3: { marginBottom: 8, fontWeight: "bold", fontSize: "12px", textTransform: "uppercase", letterSpacing: "0.75px" },
    section: { margin: 16 },
    li: { display: "flex", alignItems: "center", margin: "16px 0", fontSize: "14px" },
    svg: { marginRight: 16 },
    input: { appearance: "none", border: 0 },
});

function VanillaIconsSet({ icons }) {
    const iconTypes = Object.keys(icons) as IconType[];
    const grouped = groupBy(iconTypes, (icon) => icon.split("-")[0]);
    return (
        <div className={vanillaIconsStyle}>
            {Object.entries(grouped).map(([key, values]) => {
                return (
                    <section key={key}>
                        <h3>{labelize(key)}</h3>
                        <ul>
                            {values.map((value) => (
                                <li key={value}>
                                    <Icon icon={value} />
                                    <input
                                        type="text"
                                        onClick={(event) => (event.target as HTMLInputElement).select()}
                                        value={value}
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                );
            })}
        </div>
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
            <StoryHeading>@vanilla/icons</StoryHeading>
            <VanillaIconsSet icons={iconRegistry.getAllIcons()} />
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
