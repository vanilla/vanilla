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

const reactionsStory = storiesOf("Components/Icons", module);

reactionsStory.add("Icons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Global Icons</StoryHeading>

            <StoryHeading>Common</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"rightChevron()"}>{commonIcons.rightChevron()}</StoryTile>
                <StoryTile mouseOverText={"leftChevron()"}>{commonIcons.leftChevron()}</StoryTile>
                <StoryTile mouseOverText={"topChevron()"}>{commonIcons.topChevron()}</StoryTile>
                <StoryTile mouseOverText={"bottomChevron()"}>{commonIcons.bottomChevron()}</StoryTile>
                <StoryTile mouseOverText={"close()"}>{commonIcons.close()}</StoryTile>
                <StoryTile mouseOverText={"clear()"}>{commonIcons.clear()}</StoryTile>
                <StoryTile mouseOverText={"check()"}>{commonIcons.check()}</StoryTile>
                <StoryTile mouseOverText={"dropDownMenu()"}>{commonIcons.dropDownMenu()}</StoryTile>
                <StoryTile mouseOverText={"newFolder()"}>{commonIcons.newFolder()}</StoryTile>
                <StoryTile mouseOverText={"categoryIcon()"}>{commonIcons.categoryIcon()}</StoryTile>
                <StoryTile mouseOverText={"checkCompact()"}>{commonIcons.checkCompact()}</StoryTile>
                <StoryTile mouseOverText={"downTriangle()"}>{commonIcons.downTriangle()}</StoryTile>
                <StoryTile mouseOverText={"rightTriangle()"}>{commonIcons.rightTriangle()}</StoryTile>
                <StoryTile mouseOverText={"help()"}>{commonIcons.help()}</StoryTile>
                <StoryTile mouseOverText={"compose()"}>{commonIcons.compose()}</StoryTile>
                <StoryTile mouseOverText={"plusCircle()"}>{commonIcons.plusCircle()}</StoryTile>
                <StoryTile mouseOverText={"signIn()"}>{commonIcons.signIn()}</StoryTile>
                <StoryTile mouseOverText={"chevronUp()"}>{commonIcons.chevronUp()}</StoryTile>
                <StoryTile mouseOverText={"searchError()"}>{commonIcons.searchError()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"bold()"}>{editorIcons.bold()}</StoryTile>
                <StoryTile mouseOverText={"italic()"}>{editorIcons.italic()}</StoryTile>
                <StoryTile mouseOverText={"strike()"}>{editorIcons.strike()}</StoryTile>
                <StoryTile mouseOverText={"code()"}>{editorIcons.code()}</StoryTile>
                <StoryTile mouseOverText={"link()"}>{editorIcons.link()}</StoryTile>
                <StoryTile mouseOverText={"emoji()"}>{editorIcons.emoji()}</StoryTile>
                <StoryTile mouseOverText={"embedError()"}>{editorIcons.embedError()}</StoryTile>
                <StoryTile mouseOverText={"pilcrow()"}>{editorIcons.pilcrow()}</StoryTile>
                <StoryTile mouseOverText={"heading2()"}>{editorIcons.heading2()}</StoryTile>
                <StoryTile mouseOverText={"heading3()"}>{editorIcons.heading3()}</StoryTile>
                <StoryTile mouseOverText={"heading4()"}>{editorIcons.heading4()}</StoryTile>
                <StoryTile mouseOverText={"heading5()"}>{editorIcons.heading5()}</StoryTile>
                <StoryTile mouseOverText={"blockquote()"}>{editorIcons.blockquote()}</StoryTile>
                <StoryTile mouseOverText={"codeBlock()"}>{editorIcons.codeBlock()}</StoryTile>
                <StoryTile mouseOverText={"spoiler()"}>{editorIcons.spoiler()}</StoryTile>
                <StoryTile mouseOverText={"embed()"}>{editorIcons.embed()}</StoryTile>
                <StoryTile mouseOverText={"image()"}>{editorIcons.image()}</StoryTile>
                <StoryTile mouseOverText={"attachment()"}>{editorIcons.attachment()}</StoryTile>
                <StoryTile mouseOverText={"listUnordered()"}>{editorIcons.listUnordered()}</StoryTile>
                <StoryTile mouseOverText={"listOrdered()"}>{editorIcons.listOrdered()}</StoryTile>
                <StoryTile mouseOverText={"indent()"}>{editorIcons.indent()}</StoryTile>
                <StoryTile mouseOverText={"outdent()"}>{editorIcons.outdent()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Emoji Groups</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"emojiGroup_smileysPeople()"}>
                    {emojiGroupIcons.emojiGroup_smileysPeople()}
                </StoryTile>
                <StoryTile mouseOverText={"emojiGroup_animalsNature()"}>
                    {emojiGroupIcons.emojiGroup_animalsNature()}
                </StoryTile>
                <StoryTile mouseOverText={"emojiGroup_foodDrink()"}>{emojiGroupIcons.emojiGroup_foodDrink()}</StoryTile>
                <StoryTile mouseOverText={"emojiGroup_travelPlaces()"}>
                    {emojiGroupIcons.emojiGroup_travelPlaces()}
                </StoryTile>
                <StoryTile mouseOverText={"emojiGroup_activities()"}>
                    {emojiGroupIcons.emojiGroup_activities()}
                </StoryTile>
                <StoryTile mouseOverText={"emojiGroup_objects()"}>{emojiGroupIcons.emojiGroup_objects()}</StoryTile>
                <StoryTile mouseOverText={"emojiGroup_symbols()"}>{emojiGroupIcons.emojiGroup_symbols()}</StoryTile>
                <StoryTile mouseOverText={"emojiGroup_flags()"}>{emojiGroupIcons.emojiGroup_flags()}</StoryTile>
            </StoryTiles>
            <StoryHeading>File Types</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"fileGeneric()"}>{fileTypesIcons.fileGeneric()}</StoryTile>
                <StoryTile mouseOverText={"fileWord()"}>{fileTypesIcons.fileWord()}</StoryTile>
                <StoryTile mouseOverText={"fileExcel()"}>{fileTypesIcons.fileExcel()}</StoryTile>
                <StoryTile mouseOverText={"filePDF()"}>{fileTypesIcons.filePDF()}</StoryTile>
                <StoryTile mouseOverText={"fileImage()"}>{fileTypesIcons.fileImage()}</StoryTile>
                <StoryTile mouseOverText={"filePowerPoint()"}>{fileTypesIcons.filePowerPoint()}</StoryTile>
                <StoryTile mouseOverText={"fileZip()"}>{fileTypesIcons.fileZip()}</StoryTile>
                <StoryTile mouseOverText={"attachmentError()"}>{fileTypesIcons.attachmentError()}</StoryTile>
            </StoryTiles>
            <StoryHeading>Title Bar</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"close()"}>{headerIcons.close()}</StoryTile>
                <StoryTile mouseOverText={"check()"}>{headerIcons.check()}</StoryTile>
                <StoryTile mouseOverText={"help()"}>{headerIcons.help()}</StoryTile>
                <StoryTile mouseOverText={"compose()"}>{headerIcons.compose()}</StoryTile>
                <StoryTile mouseOverText={"download()"}>{headerIcons.download()}</StoryTile>
                <StoryTile mouseOverText={"settings()"}>{headerIcons.settings()}</StoryTile>
                <StoryTile mouseOverText={"search()"}>{headerIcons.search()}</StoryTile>
                <StoryTile mouseOverText={"notifications()"}>{headerIcons.notifications()}</StoryTile>
                <StoryTile mouseOverText={"messages()"}>{headerIcons.messages()}</StoryTile>
                <StoryTile mouseOverText={"user()"}>{headerIcons.user()}</StoryTile>
                <StoryTile mouseOverText={"userWarning()"}>{headerIcons.userWarning()}</StoryTile>
                <StoryTile mouseOverText={"noUserPhoto()"}>
                    <div className={"icon"}>{headerIcons.noUserPhoto("icon")}</div>
                </StoryTile>
                <StoryTile mouseOverText={"vanillaLogo()"} scaleContents={0.5}>
                    {headerIcons.vanillaLogo()}
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Revisions</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={"revisionStatus_draft()"}>{revisionIcons.revisionStatus_draft()}</StoryTile>
                <StoryTile mouseOverText={"revisionStatus_pending()"}>
                    {revisionIcons.revisionStatus_pending()}
                </StoryTile>
                <StoryTile mouseOverText={"revisionStatus_published()"}>
                    {revisionIcons.revisionStatus_published()}
                </StoryTile>
            </StoryTiles>
        </StoryContent>
    );
});
