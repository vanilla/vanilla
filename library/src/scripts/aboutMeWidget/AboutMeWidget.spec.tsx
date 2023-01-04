/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor } from "@testing-library/react";
import { EditProfileFieldsFixture } from "@library/editProfileFields/__fixtures__/EditProfileFields.fixtures";
import { AboutMeWidget } from "@library/aboutMeWidget/AboutMeWidget";
import { IUserProfilesState } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { stableObjectHash } from "@vanilla/utils";
import {
    ProfileField,
    ProfileFieldFormType,
    UserProfileFields,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { LoadStatus } from "@library/@types/api/core";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";

const lastField = { apiName: "lastField", label: "field-3", sort: 3 };
const firstField = { apiName: "firstField", label: "field-1", sort: 1 };
const secondField = { apiName: "secondField", label: "field-2", sort: 2 };

const mockFields: ProfileField[] = [
    ProfileFieldsFixture.mockProfileField(ProfileFieldFormType.TEXT, lastField),
    ProfileFieldsFixture.mockProfileField(ProfileFieldFormType.TEXT, firstField),
    ProfileFieldsFixture.mockProfileField(ProfileFieldFormType.TEXT, secondField),
];

const mockFieldsApiNames = mockFields.map(({ apiName }) => apiName);
const mockFieldsByApiName = Object.fromEntries(mockFields.map((field) => [field.apiName, field]));

const mockUserID = 123;

const mockUserProfileFieldsData: UserProfileFields = {
    lastField: "last field value",
    firstField: "first field value",
    secondField: "second field value",
};

const reducerState: IUserProfilesState = {
    profileFieldApiNamesByParamHash: {
        [stableObjectHash({})]: {
            status: LoadStatus.SUCCESS,
            data: mockFieldsApiNames,
        },
    },
    profileFieldsByApiName: mockFieldsByApiName,
    profileFieldsByUserID: {
        [mockUserID]: {
            status: LoadStatus.SUCCESS,
            data: mockUserProfileFieldsData,
        },
    },
    deleteStatusByApiName: {},
};

describe("AboutMeWidget", () => {
    it("displays the configured profile fields in the configured order", async () => {
        const { queryAllByText } = render(
            EditProfileFieldsFixture.createMockProfileFieldsProvider(<AboutMeWidget userID={mockUserID} />, {
                userProfiles: reducerState,
            }),
        );

        await waitFor(async () => {
            const fields = queryAllByText(/field-[1-3]/);
            expect(fields).toHaveLength(3);
            expect(fields[0]).toHaveTextContent(firstField.label!);
            expect(fields[1]).toHaveTextContent(secondField.label!);
            expect(fields[2]).toHaveTextContent(lastField.label!);
        });
    });
});
