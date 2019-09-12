/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryExampleDropDown } from "./StoryExampleDropDown";
import { FlyoutType } from "@library/flyouts/DropDown";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import StoryExampleMessagesDropDown from "@library/embeddedContent/StoryExampleDropDownMessages";
import StorybookExampleNotificationsDropDown from "@library/headers/mebox/pieces/StorybookExampleNotificationsDropDown";

const story = storiesOf("Components", module);

// Radio as tabs

const meBoxData = {
    currentUser: {
        status: "SUCCESS",
        data: {
            userID: 2,
            name: "admin",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/defaulticon.png",
            dateLastActive: "2019-08-21T12:43:58+00:00",
            isAdmin: true,
            countUnreadNotifications: 0,
            permissions: [
                "activity.delete",
                "activity.view",
                "advancedNotifications.allow",
                "applicants.manage",
                "approval.require",
                "articles.add",
                "articles.manage",
                "badges.manage",
                "badges.moderate",
                "badges.view",
                "bans.manage",
                "community.manage",
                "community.moderate",
                "conversations.add",
                "conversations.moderate",
                "csil.report",
                "curation.manage",
                "customUpload.allow",
                "email.view",
                "features.add",
                "flag.add",
                "groups.add",
                "groups.moderate",
                "kb.view",
                "logs.delete",
                "personalInfo.view",
                "pockets.manage",
                "polls.add",
                "profilePicture.edit",
                "profiles.edit",
                "profiles.view",
                "reactions.negative.add",
                "reactions.positive.add",
                "settings.view",
                "signIn.allow",
                "site.manage",
                "staff.allow",
                "tags.add",
                "tokens.add",
                "uploads.add",
                "uploads.add",
                "users.add",
                "users.delete",
                "users.edit",
            ],
        },
    },
    className: "titleBar-meBox",
    buttonClassName: "titleBar-button_fhn7z8",
    contentClassName: "titleBar-dropDownContents titleBar-dropDownContents_fto1o3a",
};

story.add("Dropdowns", () => {
    const doNothing = () => {
        return;
    };

    const toolBarColors = titleBarVariables().colors;

    return (
        <StoryContent>
            <StoryHeading depth={1}>Drop Down</StoryHeading>
            <StoryParagraph>
                Note that these dropdowns can easily be transformed into modals on mobile by using the
                &quot;openAsModal&quot; property.
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact>
                    <StoryExampleDropDown
                        flyoutType={FlyoutType.LIST}
                        title={"As List"}
                        text={"Expects all chidren to be `<li/>`"}
                    />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact backgroundColor={toolBarColors.bg}>
                    <StoryExampleMessagesDropDown />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact backgroundColor={toolBarColors.bg}>
                    <StorybookExampleNotificationsDropDown />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
