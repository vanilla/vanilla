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
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTile } from "@library/storybook/StoryTile";
import * as EditorIcons from "@library/icons/editorIcons";
import * as TitleBarIcons from "@library/icons/titleBar";
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

const story = storiesOf("Components", module);

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
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;RightChevronIcon/&gt;`}>
                    <CommonIcons.RightChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;LeftChevronIcon/&gt;`}>
                    <CommonIcons.LeftChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;TopChevronIcon/&gt;`}>
                    <CommonIcons.TopChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;BottomChevronIcon/&gt;`}>
                    <CommonIcons.BottomChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ClearIcon/&gt;`}>
                    <CommonIcons.ClearIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CheckIcon/&gt;`}>
                    <CommonIcons.CheckIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;DropDownMenuIcon/&gt;`}>
                    <CommonIcons.DropDownMenuIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;NewFolderIcon/&gt;`}>
                    <CommonIcons.NewFolderIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CategoryIcon/&gt;`}>
                    <CommonIcons.CategoryIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CheckCompactIcon/&gt;`}>
                    <CommonIcons.CheckCompactIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;DownTriangleIcon/&gt;`}>
                    <CommonIcons.DownTriangleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;RightTriangleIcon/&gt;`}>
                    <CommonIcons.RightTriangleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;HelpIcon/&gt;`}>
                    <CommonIcons.HelpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ComposeIcon/&gt;`}>
                    <CommonIcons.ComposeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;PlusCircleIcon/&gt;`}>
                    <CommonIcons.PlusCircleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SignInIcon/&gt;`}>
                    <CommonIcons.SignInIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ChevronUpIcon/&gt;`}>
                    <CommonIcons.ChevronUpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SearchErrorIcon/&gt;`}>
                    <CommonIcons.SearchErrorIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ClearIcon/&gt;`}>
                    <CommonIcons.ClearIcon />
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;BoldIcon/&gt;`}>
                    <EditorIcons.BoldIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ItalicIcon/&gt;`}>
                    <EditorIcons.ItalicIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;StrikeIcon/&gt;`}>
                    <EditorIcons.StrikeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CodeIcon/&gt;`}>
                    <EditorIcons.CodeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;LinkIcon/&gt;`}>
                    <EditorIcons.LinkIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiIcon/&gt;`}>
                    <EditorIcons.EmojiIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmbedErrorIcon/&gt;`}>
                    <EditorIcons.EmbedErrorIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;PilcrowIcon/&gt;`}>
                    <EditorIcons.PilcrowIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;Heading2Icon/&gt;`}>
                    <EditorIcons.Heading2Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;Heading3Icon/&gt;`}>
                    <EditorIcons.Heading3Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;Heading4Icon/&gt;`}>
                    <EditorIcons.Heading4Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;Heading5Icon/&gt;`}>
                    <EditorIcons.Heading5Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;BlockquoteIcon/&gt;`}>
                    <EditorIcons.BlockquoteIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CodeBlockIcon/&gt;`}>
                    <EditorIcons.CodeBlockIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SpoilerIcon/&gt;`}>
                    <EditorIcons.SpoilerIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmbedIcon/&gt;`}>
                    <EditorIcons.EmbedIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ImageIcon/&gt;`}>
                    <EditorIcons.ImageIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;AttachmentIcon/&gt;`}>
                    <EditorIcons.AttachmentIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ListUnorderedIcon/&gt;`}>
                    <EditorIcons.ListUnorderedIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ListOrderedIcon/&gt;`}>
                    <EditorIcons.ListOrderedIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;IndentIcon/&gt;`}>
                    <EditorIcons.IndentIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;OutdentIcon/&gt;`}>
                    <EditorIcons.OutdentIcon />
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Emoji Groups</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;EmojiGroupSmileysPeopleIcon/&gt;`}>
                    <EmojiGroupSmileysPeopleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupAnimalsNatureIcon/&gt;`}>
                    <EmojiGroupAnimalsNatureIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupFoodDrinkIcon/&gt;`}>
                    <EmojiGroupFoodDrinkIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupTravelPlacesIcon/&gt;`}>
                    <EmojiGroupTravelPlacesIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupActivitiesIcon/&gt;`}>
                    <EmojiGroupActivitiesIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupObjectsIcon/&gt;`}>
                    <EmojiGroupObjectsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupSymbolsIcon/&gt;`}>
                    <EmojiGroupSymbolsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;EmojiGroupFlagsIcon/&gt;`}>
                    <EmojiGroupFlagsIcon />
                </StoryTile>
            </StoryTiles>
            <StoryHeading>File Types</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;FileTypeGenericIcon/&gt;`}>
                    <FileTypeGenericIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypeWordIcon/&gt;`}>
                    <FileTypeWordIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypeExcelIcon/&gt;`}>
                    <FileTypeExcelIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypePDFIcon/&gt;`}>
                    <FileTypePDFIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypeImageIcon/&gt;`}>
                    <FileTypeImageIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypePowerPointIcon/&gt;`}>
                    <FileTypePowerPointIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;FileTypeZipIcon/&gt;`}>
                    <FileTypeZipIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;AttachmentErrorIcon/&gt;`}>
                    <AttachmentErrorIcon />
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Title Bar</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;CheckIcon/&gt;`}>
                    <TitleBarIcons.CheckIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;HelpIcon/&gt;`}>
                    <TitleBarIcons.HelpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ComposeIcon/&gt;`}>
                    <TitleBarIcons.ComposeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;DownloadIcon/&gt;`}>
                    <TitleBarIcons.DownloadIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SettingsIcon/&gt;`}>
                    <TitleBarIcons.SettingsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SearchIcon/&gt;`}>
                    <TitleBarIcons.SearchIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;NotificationsIcon/&gt;`}>
                    <TitleBarIcons.NotificationsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;MessagesIcon/&gt;`}>
                    <TitleBarIcons.MessagesIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;UserIcon/&gt;`}>
                    <TitleBarIcons.UserIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;UserWarningIcon/&gt;`}>
                    <TitleBarIcons.UserWarningIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;NoUserPhotoIcon/&gt;`}>
                    <div className={"icon"}>
                        <TitleBarIcons.NoUserPhotoIcon />
                    </div>
                </StoryTile>
                <StoryTile mouseOverText={`&lt;VanillaLogo/>`} scaleContents={0.5}>
                    {<TitleBarIcons.VanillaLogo />}
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Revisions</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;RevisionStatusDraftIcon/&gt;`}>
                    <RevisionStatusDraftIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;RevisionStatusPendingIcon/&gt;`}>
                    <RevisionStatusPendingIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;RevisionStatusPublishedIcon/&gt;`}>
                    <RevisionStatusPublishedIcon />
                </StoryTile>
            </StoryTiles>
        </StoryContent>
    );
});
