/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import Paragraph from "@library/layout/Paragraph";
import { StoryUnorderedList } from "@library/storybook/StoryUnorderedList";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryLink } from "@library/storybook/StoryLink";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTile } from "@library/storybook/StoryTile";
import * as commonIcons from "@library/icons/common";
import * as editorIcons from "@library/icons/editorIcons";
import * as emojiGroupIcons from "@library/icons/emojiGroups";
import * as fileTypesIcons from "@library/icons/fileTypes";
import * as headerIcons from "@library/icons/titleBar";
import * as revisionIcons from "@library/icons/revision";

const reactionsStory = storiesOf("Icons", module);

reactionsStory.add("Icons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Global Icons</StoryHeading>

            <StoryHeading>Common</StoryHeading>
            <StoryTiles>
                <StoryTile title={"rightChevron()"}>{commonIcons.rightChevron()}</StoryTile>
                <StoryTile title={"leftChevron()"}>{commonIcons.leftChevron()}</StoryTile>
                <StoryTile title={"topChevron()"}>{commonIcons.topChevron()}</StoryTile>
                <StoryTile title={"bottomChevron()"}>{commonIcons.bottomChevron()}</StoryTile>
                <StoryTile title={"close()"}>{commonIcons.close()}</StoryTile>
                <StoryTile title={"clear()"}>{commonIcons.clear()}</StoryTile>
                <StoryTile title={"check()"}>{commonIcons.check()}</StoryTile>
                <StoryTile title={"dropDownMenu()"}>{commonIcons.dropDownMenu()}</StoryTile>
                <StoryTile title={"newFolder()"}>{commonIcons.newFolder()}</StoryTile>
                <StoryTile title={"categoryIcon()"}>{commonIcons.categoryIcon()}</StoryTile>
                <StoryTile title={"checkCompact()"}>{commonIcons.checkCompact()}</StoryTile>
                <StoryTile title={"downTriangle()"}>{commonIcons.downTriangle()}</StoryTile>
                <StoryTile title={"rightTriangle()"}>{commonIcons.rightTriangle()}</StoryTile>
                <StoryTile title={"help()"}>{commonIcons.help()}</StoryTile>
                <StoryTile title={"compose()"}>{commonIcons.compose()}</StoryTile>
                <StoryTile title={"plusCircle()"}>{commonIcons.plusCircle()}</StoryTile>
                <StoryTile title={"signIn()"}>{commonIcons.signIn()}</StoryTile>
                <StoryTile title={"chevronUp()"}>{commonIcons.chevronUp()}</StoryTile>
                <StoryTile title={"searchError()"}>{commonIcons.searchError()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTile title={"bold()"}>{editorIcons.bold()}</StoryTile>
                <StoryTile title={"italic()"}>{editorIcons.italic()}</StoryTile>
                <StoryTile title={"strike()"}>{editorIcons.strike()}</StoryTile>
                <StoryTile title={"code()"}>{editorIcons.code()}</StoryTile>
                <StoryTile title={"link()"}>{editorIcons.link()}</StoryTile>
                <StoryTile title={"emoji()"}>{editorIcons.emoji()}</StoryTile>
                <StoryTile title={"embedError()"}>{editorIcons.embedError()}</StoryTile>
                <StoryTile title={"pilcrow()"}>{editorIcons.pilcrow()}</StoryTile>
                <StoryTile title={"heading2()"}>{editorIcons.heading2()}</StoryTile>
                <StoryTile title={"heading3()"}>{editorIcons.heading3()}</StoryTile>
                <StoryTile title={"heading4()"}>{editorIcons.heading4()}</StoryTile>
                <StoryTile title={"heading5()"}>{editorIcons.heading5()}</StoryTile>
                <StoryTile title={"blockquote()"}>{editorIcons.blockquote()}</StoryTile>
                <StoryTile title={"codeBlock()"}>{editorIcons.codeBlock()}</StoryTile>
                <StoryTile title={"spoiler()"}>{editorIcons.spoiler()}</StoryTile>
                <StoryTile title={"embed()"}>{editorIcons.embed()}</StoryTile>
                <StoryTile title={"image()"}>{editorIcons.image()}</StoryTile>
                <StoryTile title={"attachment()"}>{editorIcons.attachment()}</StoryTile>
                <StoryTile title={"listUnordered()"}>{editorIcons.listUnordered()}</StoryTile>
                <StoryTile title={"listOrdered()"}>{editorIcons.listOrdered()}</StoryTile>
                <StoryTile title={"indent()"}>{editorIcons.indent()}</StoryTile>
                <StoryTile title={"outdent()"}>{editorIcons.outdent()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Emoji Groups</StoryHeading>
            <StoryTiles>
                <StoryTile title={"emojiGroup_smileysPeople()"}>{emojiGroupIcons.emojiGroup_smileysPeople()}</StoryTile>
                <StoryTile title={"emojiGroup_animalsNature()"}>{emojiGroupIcons.emojiGroup_animalsNature()}</StoryTile>
                <StoryTile title={"emojiGroup_foodDrink()"}>{emojiGroupIcons.emojiGroup_foodDrink()}</StoryTile>
                <StoryTile title={"emojiGroup_travelPlaces()"}>{emojiGroupIcons.emojiGroup_travelPlaces()}</StoryTile>
                <StoryTile title={"emojiGroup_activities()"}>{emojiGroupIcons.emojiGroup_activities()}</StoryTile>
                <StoryTile title={"emojiGroup_objects()"}>{emojiGroupIcons.emojiGroup_objects()}</StoryTile>
                <StoryTile title={"emojiGroup_symbols()"}>{emojiGroupIcons.emojiGroup_symbols()}</StoryTile>
                <StoryTile title={"emojiGroup_flags()"}>{emojiGroupIcons.emojiGroup_flags()}</StoryTile>
            </StoryTiles>
            <StoryHeading>File Types</StoryHeading>
            <StoryTiles>
                <StoryTile title={"fileGeneric()"}>{fileTypesIcons.fileGeneric()}</StoryTile>
                <StoryTile title={"fileWord()"}>{fileTypesIcons.fileWord()}</StoryTile>
                <StoryTile title={"fileExcel()"}>{fileTypesIcons.fileExcel()}</StoryTile>
                <StoryTile title={"filePDF()"}>{fileTypesIcons.filePDF()}</StoryTile>
                <StoryTile title={"fileImage()"}>{fileTypesIcons.fileImage()}</StoryTile>
                <StoryTile title={"filePowerPoint()"}>{fileTypesIcons.filePowerPoint()}</StoryTile>
                <StoryTile title={"fileZip()"}>{fileTypesIcons.fileZip()}</StoryTile>
                <StoryTile title={"attachmentError()"}>{fileTypesIcons.attachmentError()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Title Bar</StoryHeading>
            <StoryTiles>
                <StoryTile title={"close()"}>{headerIcons.close()}</StoryTile>
                <StoryTile title={"check()"}>{headerIcons.check()}</StoryTile>
                <StoryTile title={"help()"}>{headerIcons.help()}</StoryTile>
                <StoryTile title={"compose()"}>{headerIcons.compose()}</StoryTile>
                <StoryTile title={"download()"}>{headerIcons.download()}</StoryTile>
                <StoryTile title={"settings()"}>{headerIcons.settings()}</StoryTile>
                <StoryTile title={"search()"}>{headerIcons.search()}</StoryTile>
                <StoryTile title={"notifications()"}>{headerIcons.notifications()}</StoryTile>
                <StoryTile title={"messages()"}>{headerIcons.messages()}</StoryTile>
                <StoryTile title={"user()"}>{headerIcons.user()}</StoryTile>
                <StoryTile title={"userWarning()"}>{headerIcons.userWarning()}</StoryTile>
                <StoryTile title={"noUserPhoto()"}>
                    <div className={"icon"}>{headerIcons.noUserPhoto("icon")}</div>
                </StoryTile>
                <StoryTile title={"vanillaLogo()"} scaleContents={0.5}>
                    {headerIcons.vanillaLogo()}
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Revisions</StoryHeading>
            <StoryTiles>
                <StoryTile title={"revisionStatus_draft()"}>{revisionIcons.revisionStatus_draft()}</StoryTile>
                <StoryTile title={"revisionStatus_pending()"}>{revisionIcons.revisionStatus_pending()}</StoryTile>
                <StoryTile title={"revisionStatus_published()"}>{revisionIcons.revisionStatus_published()}</StoryTile>
            </StoryTiles>
        </StoryContent>
    );
});
