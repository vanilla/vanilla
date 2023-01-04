/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { userProfilesSlice } from "@dashboard/userProfiles/state/UserProfiles.slice";
import {
    ProfileField,
    ProfileFieldDataType,
    ProfileFieldFormType,
    ProfileFieldMutability,
    ProfileFieldRegistrationOptions,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import DashboardAddEditUser from "@dashboard/users/DashboardAddEditUser";
import { IUser } from "@library/@types/api/users";
import { registerReducer } from "@library/redux/reducerRegistry";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
export default {
    title: "Dashboard/AddEditUser",
};

//FIXME FIXME this story is broken, now we have couple of those
//(Changing push() to unshift() in makeStoryConfig.ts baseStorybookConfig.plugins helped but tweaked bunch of other stories)
//just like this it generates on the production build on the server only(but looks broken),but not locally on 'test' mode
// to see the real it, make the change describied above and remove fixme from file name
//https://higherlogic.atlassian.net/browse/VNLA-1459

registerReducer(userProfilesSlice.name, userProfilesSlice.reducer);

const mockUser: Partial<IUser> = {
    name: "TestUser",
    email: "testuser@test.com",
    userID: 1,
    photoUrl: "",
    dateLastActive: "",
    profileFields: {
        test1: "red",
        test2_tokens: ["Volvo"],
    },
    rankID: 3,
};

const mockProfileFields: ProfileField[] = [
    {
        apiName: "test1",
        label: "My favourite color",
        formType: ProfileFieldFormType.TEXT,
        description: "Just a description",
        registrationOptions: ProfileFieldRegistrationOptions.REQUIRED,
        visibility: ProfileFieldVisibility.PUBLIC,
        mutability: ProfileFieldMutability.ALL,
        displayOptions: {
            posts: true,
            userCards: false,
        },
        dataType: ProfileFieldDataType.TEXT,
        enabled: true,
    },
    {
        apiName: "test2_tokens",
        label: "My cars",
        formType: ProfileFieldFormType.TOKENS,
        description: "Just a description",
        registrationOptions: ProfileFieldRegistrationOptions.OPTIONAL,
        visibility: ProfileFieldVisibility.PUBLIC,
        mutability: ProfileFieldMutability.ALL,
        displayOptions: {
            posts: true,
            userCards: false,
        },
        dropdownOptions: ["Volvo", "Fiat", "Jeep"],
        dataType: ProfileFieldDataType.TEXT,
        enabled: true,
    },
];

export function DashboardAddUserModal() {
    return (
        <>
            <StoryHeading>Add User Modal, basic user fields</StoryHeading>
            <DashboardAddEditUser profileFields={[]} forceModalVisibility />
        </>
    );
}

export function DashboardAddUserModalWithProfileFields() {
    return (
        <>
            <StoryHeading>Add User Modal, with profile fields and ranks</StoryHeading>
            <DashboardAddEditUser
                ranks={{ 3: "Level3", 4: "Level 4" }}
                profileFields={mockProfileFields}
                forceModalVisibility
            />
        </>
    );
}

export function DashboardEditUserModalWithProfileFields() {
    return (
        <>
            <StoryHeading>Edit User Modal, with injected user data</StoryHeading>
            <DashboardAddEditUser
                ranks={{ 3: "Level3", 4: "Level 4" }}
                profileFields={mockProfileFields}
                userData={mockUser}
                forceModalVisibility
            />
        </>
    );
}
