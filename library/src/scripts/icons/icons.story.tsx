/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import * as EditorIcons from "@library/icons/editorIcons";
import * as TitleBarIcons from "@library/icons/titleBar";
import * as CommonIcons from "@library/icons/common";
import * as EmojiGroupIcons from "@library/icons/emojiGroups";
import * as FileTypeIcons from "@library/icons/fileTypes";
import * as RevisionIcons from "@library/icons/revision";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { color } from "csx";
import { coreIconsData, Icon } from "@vanilla/icons";
import { css } from "@emotion/css";
import groupBy from "lodash-es/groupBy";
import { labelize } from "@vanilla/utils";
import { IconType } from "@vanilla/icons";
import { Dictionary } from "@reduxjs/toolkit";

export default {
    title: "Components",
};

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
    h3: { marginBottom: 16, fontWeight: "bold", fontSize: "18px", textTransform: "uppercase", letterSpacing: "0.75px" },
    section: { margin: "0 0 36px" },
    ul: { display: "flex", flexWrap: "wrap", padding: 0, listStyle: "none", gap: 8 },
    li: {
        display: "grid",
        gridTemplateColumns: "1fr",
        gridTemplateRows: "auto 32px",
        width: 150,
        height: 150,
        border: "1px solid rgb(170,173,177)",
        borderRadius: 8,
        padding: 16,
        cursor: "pointer",
    },
    svg: { transform: "scale(1.2)", alignSelf: "center", justifySelf: "center" },
    span: { textAlign: "center", alignSelf: "center", justifySelf: "center" },
});

function VanillaIconsSet() {
    const iconTypes = Object.keys(coreIconsData) as string[];
    const grouped = groupBy(iconTypes, (icon) => {
        const split = icon.split("-");
        return split.length > 1 ? split[0] : "Ungrouped";
    });
    const sorted: Dictionary<string[]> = Object.entries(grouped).reduce(
        (acc, [key, values]) => {
            if (values.length > 1) {
                return { ...acc, [key]: values.sort() };
            }
            return { ...acc, Ungrouped: [...acc?.["Ungrouped"], ...values].sort() };
        },
        { Ungrouped: [] },
    );

    return (
        <div className={vanillaIconsStyle}>
            {Object.entries(sorted).map(([key, values]) => {
                return (
                    <section key={key}>
                        <h3>{labelize(key)}</h3>
                        <ul>
                            {values?.map((value) => (
                                <li
                                    key={value}
                                    onClick={(event) => {
                                        const selection = window.getSelection();
                                        const range = document.createRange();
                                        const node = event.currentTarget.children[1];
                                        if (node) {
                                            range.selectNodeContents(node);
                                            selection?.removeAllRanges();
                                            selection?.addRange(range);
                                        }
                                    }}
                                >
                                    <Icon icon={value as IconType} />
                                    <span>{value}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                );
            })}
        </div>
    );
}

export const Icons = () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Global Icons</StoryHeading>
                <StoryParagraph>
                    Use a function to call these icons. Note also that some of these functions have &quot;compact&quot;
                    versions, which just means they have less padding for tighter spaces. Most icons render in a box
                    24px by 24px.
                </StoryParagraph>
                <StoryHeading>@vanilla/icons</StoryHeading>
            </StoryContent>
            <VanillaIconsSet />
            <StoryContent>
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
            </StoryContent>
        </>
    );
};
