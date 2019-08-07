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
                <StoryTile mouseOverText={`&lt;RightChevronIconLogo/&gt;`}>
                    <CommonIcons.RightChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;leftChevron/&gt;`}>
                    <CommonIcons.LeftChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;TopChevronIcon/&gt;`}>
                    <CommonIcons.TopChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;BottomChevronIcon/&gt;`}>
                    <CommonIcons.BottomChevronIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CloseIcon/&gt;`}>
                    <CommonIcons.CloseIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;clearIcon/&gt;`}>
                    <CommonIcons.ClearIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;checkIcon/&gt;`}>
                    <CommonIcons.CheckIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;dropDownMenuIcon/&gt;`}>
                    <CommonIcons.DropDownMenuIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;newFolderIcon/&gt;`}>
                    <CommonIcons.NewFolderIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;categoryIconLogo/&gt;`}>
                    <CommonIcons.CategoryIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;checkCompactLogo/&gt;`}>
                    <CommonIcons.CheckCompactIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;downTriangleLogo/&gt;`}>
                    <CommonIcons.DownTriangleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;rightTriangleLogo/&gt;`}>
                    <CommonIcons.RightTriangleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;helpLogo/&gt;`}>
                    <CommonIcons.HelpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;composeLogo/&gt;`}>
                    <CommonIcons.ComposeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;plusCircleLogo/&gt;`}>
                    <CommonIcons.PlusCircleIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;signInLogo/&gt;`}>
                    <CommonIcons.SignInIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;chevronUpLogo/&gt;`}>
                    <CommonIcons.ChevronUpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;searchErrorLogo/&gt;`}>
                    <CommonIcons.SearchErrorIcon />
                </StoryTile>
            </StoryTiles>
            <StoryHeading>Editor</StoryHeading>
            <StoryTiles>
                <StoryTile mouseOverText={`&lt;boldLogo/&gt;`}>
                    <EditorIcons.BoldIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;italicLogo/&gt;`}>
                    <EditorIcons.ItalicIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;strikeLogo/&gt;`}>
                    <EditorIcons.StrikeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;codeLogo/&gt;`}>
                    <EditorIcons.CodeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;linkLogo/&gt;`}>
                    <EditorIcons.LinkIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;emojiLogo/&gt;`}>
                    <EditorIcons.EmojiIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;embedErrorLogo/&gt;`}>
                    <EditorIcons.EmbedErrorIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;pilcrowLogo/&gt;`}>
                    <EditorIcons.PilcrowIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;heading2Logo/&gt;`}>
                    <EditorIcons.Heading2Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;heading3Logo/&gt;`}>
                    <EditorIcons.Heading3Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;heading4Logo/&gt;`}>
                    <EditorIcons.Heading4Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;heading5Logo/&gt;`}>
                    <EditorIcons.Heading5Icon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;blockquoteLogo/&gt;`}>
                    <EditorIcons.BlockquoteIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;codeBlockLogo/&gt;`}>
                    <EditorIcons.CodeBlockIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;spoilerLogo/&gt;`}>
                    <EditorIcons.SpoilerIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;embedLogo/&gt;`}>
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
                <StoryTile mouseOverText={`&lt;CloseIcon/&gt;`}>
                    <TitleBarIcons.CloseIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;CheckLogo/&gt;`}>
                    <TitleBarIcons.CheckIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;HelpLogo/&gt;`}>
                    <TitleBarIcons.HelpIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;ComposeLogo/&gt;`}>
                    <TitleBarIcons.ComposeIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;DownloadLogo/&gt;`}>
                    <TitleBarIcons.DownloadIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SettingsLogo/&gt;`}>
                    <TitleBarIcons.SettingsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;SearchLogo/&gt;`}>
                    <TitleBarIcons.SearchIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;NotificationsLogo/&gt;`}>
                    <TitleBarIcons.NotificationsIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;MessagesLogo/&gt;`}>
                    <TitleBarIcons.MessagesIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;UserLogo/&gt;`}>
                    <TitleBarIcons.UserIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;UserWarningLogo/&gt;`}>
                    <TitleBarIcons.UserWarningIcon />
                </StoryTile>
                <StoryTile mouseOverText={`&lt;NoUserPhotoLogo/&gt;`}>
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
