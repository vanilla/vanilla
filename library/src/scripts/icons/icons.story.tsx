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
import * as titleBarIcons from "@library/icons/titleBar";
import * as CommonIcons from "@library/icons/common";
import {
    EmojiGroupSmileysPeopleIcon,
    EmojiGroupAnimalsNatureIcon,
    EmojiGroupFoodDrinkIcon,
    EmojiGroupTravelPlacesIcon,
    EmojiGroupActivitiesIcon,
    EmojiGroupObjectsIcon,
    EmojiGroupSymbolsIcon,
    EmojiGroupFlagsIcon,
} from "./emojiGroups";
import {
    FileTypeGenericIcon,
    FileTypeWordIcon,
    FileTypeExcelIcon,
    FileTypeImageIcon,
    FileTypePowerPointIcon,
    FileTypePDFIcon,
    FileTypeZipIcon,
    AttachmentErrorIcon,
} from "@library/icons/fileTypes";
import {
    RevisionStatusPublishedIcon,
    RevisionStatusPendingIcon,
    RevisionStatusDraftIcon,
} from "@library/icons/revision";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

const story = storiesOf("Components", module);

story.add("Icons", () => {
    const classes = storyBookClasses();
    return (
        <StoryContent>
            <StoryHeading depth={1}>Global Icons</StoryHeading>
            <StoryParagraph>
                Use a function to call these icons. Note also that some of these functions have &quot;compact&quot;
                versions, which just means they have less padding for tighter spaces. Most icons render in a box 24px by
                24px.
            </StoryParagraph>

            <StoryHeading>Common</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`RightChevronIcon`}>
                    <CommonIcons.RightChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`LeftChevronIcon`}>
                    <CommonIcons.LeftChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`TopChevronIcon`}>
                    <CommonIcons.TopChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`BottomChevronIcon`}>
                    <CommonIcons.BottomChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ClearIcon`}>
                    <CommonIcons.ClearIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CheckIcon`}>
                    <CommonIcons.CheckIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`DropDownMenuIcon`}>
                    <CommonIcons.DropDownMenuIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`NewFolderIcon`}>
                    <CommonIcons.NewFolderIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CategoryIcon`}>
                    <CommonIcons.CategoryIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CheckCompactIcon`}>
                    <CommonIcons.CheckCompactIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`DownTriangleIcon`}>
                    <CommonIcons.DownTriangleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`RightTriangleIcon`}>
                    <CommonIcons.RightTriangleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`HelpIcon`}>
                    <CommonIcons.HelpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ComposeIcon`}>
                    <CommonIcons.ComposeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`PlusCircleIcon`}>
                    <CommonIcons.PlusCircleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`SignInIcon`}>
                    <CommonIcons.SignInIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ChevronUpIcon`}>
                    <CommonIcons.ChevronUpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`SearchErrorIcon`}>
                    <CommonIcons.SearchErrorIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CloseIcon`}>
                    <CommonIcons.CloseIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ClearIcon`}>
                    <CommonIcons.ClearIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CloseTinyIcon`}>
                    <CommonIcons.CloseTinyIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`BoldIcon`}>
                    <EditorIcons.BoldIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ItalicIcon`}>
                    <EditorIcons.ItalicIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`StrikeIcon`}>
                    <EditorIcons.StrikeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CodeIcon`}>
                    <EditorIcons.CodeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`LinkIcon`}>
                    <EditorIcons.LinkIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiIcon`}>
                    <EditorIcons.EmojiIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmbedErrorIcon`}>
                    <EditorIcons.EmbedErrorIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`PilcrowIcon`}>
                    <EditorIcons.PilcrowIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`Heading2Icon`}>
                    <EditorIcons.Heading2Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`Heading3Icon`}>
                    <EditorIcons.Heading3Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`Heading4Icon`}>
                    <EditorIcons.Heading4Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`Heading5Icon`}>
                    <EditorIcons.Heading5Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`BlockquoteIcon`}>
                    <EditorIcons.BlockquoteIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`CodeBlockIcon`}>
                    <EditorIcons.CodeBlockIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`SpoilerIcon`}>
                    <EditorIcons.SpoilerIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmbedIcon`}>
                    <EditorIcons.EmbedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ImageIcon`}>
                    <EditorIcons.ImageIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`AttachmentIcon`}>
                    <EditorIcons.AttachmentIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ListUnorderedIcon`}>
                    <EditorIcons.ListUnorderedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ListOrderedIcon`}>
                    <EditorIcons.ListOrderedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`IndentIcon`}>
                    <EditorIcons.IndentIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`OutdentIcon`}>
                    <EditorIcons.OutdentIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Emoji Groups</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`EmojiGroupSmileysPeopleIcon`}>
                    <EmojiGroupSmileysPeopleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupAnimalsNatureIcon`}>
                    <EmojiGroupAnimalsNatureIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupFoodDrinkIcon`}>
                    <EmojiGroupFoodDrinkIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupTravelPlacesIcon`}>
                    <EmojiGroupTravelPlacesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupActivitiesIcon`}>
                    <EmojiGroupActivitiesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupObjectsIcon`}>
                    <EmojiGroupObjectsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupSymbolsIcon`}>
                    <EmojiGroupSymbolsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`EmojiGroupFlagsIcon`}>
                    <EmojiGroupFlagsIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>File Types</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`FileTypeGenericIcon`}>
                    <FileTypeGenericIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypeWordIcon`}>
                    <FileTypeWordIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypeExcelIcon`}>
                    <FileTypeExcelIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypePDFIcon`}>
                    <FileTypePDFIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypeImageIcon`}>
                    <FileTypeImageIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypePowerPointIcon`}>
                    <FileTypePowerPointIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`FileTypeZipIcon`}>
                    <FileTypeZipIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`AttachmentErrorIcon`}>
                    <AttachmentErrorIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>title Bar</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`CheckIcon`}>
                    <titleBarIcons.CheckIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`HelpIcon`}>
                    <titleBarIcons.HelpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`ComposeIcon`}>
                    <titleBarIcons.ComposeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`DownloadIcon`}>
                    <titleBarIcons.DownloadIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`SettingsIcon`}>
                    <titleBarIcons.SettingsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`SearchIcon`}>
                    <titleBarIcons.SearchIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`NotificationsIcon`}>
                    <titleBarIcons.NotificationsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`MessagesIcon`}>
                    <titleBarIcons.MessagesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`UserIcon`}>
                    <titleBarIcons.UserIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`UserWarningIcon`}>
                    <titleBarIcons.UserWarningIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`NoUserPhotoIcon`}>
                    <div className={"icon"}>
                        <titleBarIcons.NoUserPhotoIcon />
                    </div>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`VanillaLogo`}>
                    {<titleBarIcons.VanillaLogo className={classes.smallerLogo} />}
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Revisions</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact title={`RevisionStatusDraftIcon`}>
                    <RevisionStatusDraftIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`RevisionStatusPendingIcon`}>
                    <RevisionStatusPendingIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={`RevisionStatusPublishedIcon`}>
                    <RevisionStatusPublishedIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
