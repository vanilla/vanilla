/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { AccountConflictImpl } from "@library/accountConflict/AccountConflict";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { STORY_ME_ADMIN, STORY_USER } from "@library/storybook/storyData";
import React, { useState } from "react";

export default {
    component: AccountConflictImpl,
    title: "Components/Account Conflict",
};

export const AccountConflict = storyWithConfig(
    {
        useWrappers: false,
        storeState: {
            users: {
                usersByID: {
                    [STORY_USER.userID]: {
                        status: LoadStatus.SUCCESS,
                        data: STORY_USER,
                    },
                },
            },
        },
    },
    () => {
        const [modalStatus, setModalStatus] = useState<string>("Waiting on user input.");

        const onSignOut = async () => {
            setModalStatus("User has signed out.");
        };

        const onClose = async () => {
            setModalStatus("User has closed the modal and remained signed in.");
        };

        return (
            <CurrentUserContextProvider currentUser={STORY_ME_ADMIN}>
                <StoryContent>
                    <StoryHeading>Account Conflict Modal</StoryHeading>
                    <StoryParagraph>
                        If a user is taken to a page that has a possible account conflict, this modal should be
                        displayed to prompt them to sign out and back in.
                    </StoryParagraph>
                    <StoryParagraph>
                        One example is the Unsubscribe landing pages. The user clicks on a link from an email and is
                        shown a page for the email recipient. If they are logged in as another user and want to change
                        preferences, this modal will display to alert them that they might not be going to the correct
                        account preferences. It gives them the option to sign out and back in.
                    </StoryParagraph>
                    <StoryParagraph>
                        To use the modal, the component must be added to the page{" "}
                        <code>import {AccountConflict} from &quot;@library/accountConflict/AccountConflict&quot;</code>{" "}
                        and the page&apos;s URL query string must include <code>accountConflict=true</code>. The
                        component will read the URL location and remove this parameter when it is closed.
                    </StoryParagraph>
                    <StoryParagraph>
                        <strong>Example</strong>:{" "}
                        <code>https://webaddress.com/page-path?variable=value&accountConflict=true</code>
                    </StoryParagraph>
                    <StoryParagraph>
                        Optional methods can be provided to additional actions after the user has interacted with the
                        modal. Neither method returns a value. <code>onSignOut</code> will perform additional actions
                        when the user selects to sign out. <code>onClose</code> will perform additional actions when the
                        user choose to close the modal and remain signed in.
                    </StoryParagraph>
                    <StoryParagraph>
                        <strong>Modal Status:</strong> {modalStatus}
                    </StoryParagraph>
                    <AccountConflictImpl
                        searchQuery={{ accountConflict: "true" }}
                        pathname=""
                        onSignOut={onSignOut}
                        onClose={onClose}
                    />
                </StoryContent>
            </CurrentUserContextProvider>
        );
    },
);
