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
                <StoryTileAndTextCompact text={`RightChevronIcon`}>
                    <CommonIcons.RightChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`LeftChevronIcon`}>
                    <CommonIcons.LeftChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`TopChevronIcon`}>
                    <CommonIcons.TopChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`BottomChevronIcon`}>
                    <CommonIcons.BottomChevronIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ClearIcon`}>
                    <CommonIcons.ClearIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CheckIcon`}>
                    <CommonIcons.CheckIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`DropDownMenuIcon`}>
                    <CommonIcons.DropDownMenuIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`NewFolderIcon`}>
                    <CommonIcons.NewFolderIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CategoryIcon`}>
                    <CommonIcons.CategoryIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CheckCompactIcon`}>
                    <CommonIcons.CheckCompactIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`DownTriangleIcon`}>
                    <CommonIcons.DownTriangleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`RightTriangleIcon`}>
                    <CommonIcons.RightTriangleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`HelpIcon`}>
                    <CommonIcons.HelpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ComposeIcon`}>
                    <CommonIcons.ComposeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`PlusCircleIcon`}>
                    <CommonIcons.PlusCircleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`SignInIcon`}>
                    <CommonIcons.SignInIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ChevronUpIcon`}>
                    <CommonIcons.ChevronUpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`SearchErrorIcon`}>
                    <CommonIcons.SearchErrorIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CloseIcon`}>
                    <CommonIcons.CloseIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ClearIcon`}>
                    <CommonIcons.ClearIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CloseTinyIcon`}>
                    <CommonIcons.CloseTinyIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EditIcon`}>
                    <CommonIcons.EditIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`DeleteIcon`}>
                    <CommonIcons.DeleteIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`DiscussionIcon`}>
                    <CommonIcons.DiscussionIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`GlobeIcon`}>
                    <CommonIcons.GlobeIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`BoldIcon`}>
                    <EditorIcons.BoldIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ItalicIcon`}>
                    <EditorIcons.ItalicIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`StrikeIcon`}>
                    <EditorIcons.StrikeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CodeIcon`}>
                    <EditorIcons.CodeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`LinkIcon`}>
                    <EditorIcons.LinkIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiIcon`}>
                    <EditorIcons.EmojiIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmbedErrorIcon`}>
                    <EditorIcons.EmbedErrorIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`PilcrowIcon`}>
                    <EditorIcons.PilcrowIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`Heading2Icon`}>
                    <EditorIcons.Heading2Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`Heading3Icon`}>
                    <EditorIcons.Heading3Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`Heading4Icon`}>
                    <EditorIcons.Heading4Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`Heading5Icon`}>
                    <EditorIcons.Heading5Icon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`BlockquoteIcon`}>
                    <EditorIcons.BlockquoteIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`CodeBlockIcon`}>
                    <EditorIcons.CodeBlockIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`SpoilerIcon`}>
                    <EditorIcons.SpoilerIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmbedIcon`}>
                    <EditorIcons.EmbedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ImageIcon`}>
                    <EditorIcons.ImageIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`AttachmentIcon`}>
                    <EditorIcons.AttachmentIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ListUnorderedIcon`}>
                    <EditorIcons.ListUnorderedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ListOrderedIcon`}>
                    <EditorIcons.ListOrderedIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`IndentIcon`}>
                    <EditorIcons.IndentIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`OutdentIcon`}>
                    <EditorIcons.OutdentIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Emoji Groups</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`EmojiGroupSmileysPeopleIcon`}>
                    <EmojiGroupSmileysPeopleIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupAnimalsNatureIcon`}>
                    <EmojiGroupAnimalsNatureIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupFoodDrinkIcon`}>
                    <EmojiGroupFoodDrinkIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupTravelPlacesIcon`}>
                    <EmojiGroupTravelPlacesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupActivitiesIcon`}>
                    <EmojiGroupActivitiesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupObjectsIcon`}>
                    <EmojiGroupObjectsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupSymbolsIcon`}>
                    <EmojiGroupSymbolsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`EmojiGroupFlagsIcon`}>
                    <EmojiGroupFlagsIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>File Types</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`FileTypeGenericIcon`}>
                    <FileTypeGenericIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypeWordIcon`}>
                    <FileTypeWordIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypeExcelIcon`}>
                    <FileTypeExcelIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypePDFIcon`}>
                    <FileTypePDFIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypeImageIcon`}>
                    <FileTypeImageIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypePowerPointIcon`}>
                    <FileTypePowerPointIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`FileTypeZipIcon`}>
                    <FileTypeZipIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`AttachmentErrorIcon`}>
                    <AttachmentErrorIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>title Bar</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`CheckIcon`}>
                    <titleBarIcons.CheckIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`HelpIcon`}>
                    <titleBarIcons.HelpIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`ComposeIcon`}>
                    <titleBarIcons.ComposeIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`DownloadIcon`}>
                    <titleBarIcons.DownloadIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`SettingsIcon`}>
                    <titleBarIcons.SettingsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`SearchIcon`}>
                    <titleBarIcons.SearchIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`NotificationsIcon`}>
                    <titleBarIcons.NotificationsIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`MessagesIcon`}>
                    <titleBarIcons.MessagesIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`UserIcon`}>
                    <titleBarIcons.UserIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`UserWarningIcon`}>
                    <titleBarIcons.UserWarningIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`NoUserPhotoIcon`}>
                    <div className={"icon"}>
                        <titleBarIcons.NoUserPhotoIcon />
                    </div>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`VanillaLogo`}>
                    {<titleBarIcons.VanillaLogo className={classes.smallerLogo} />}
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Revisions</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`RevisionStatusDraftIcon`}>
                    <RevisionStatusDraftIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`RevisionStatusPendingIcon`}>
                    <RevisionStatusPendingIcon />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`RevisionStatusPublishedIcon`}>
                    <RevisionStatusPublishedIcon />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
