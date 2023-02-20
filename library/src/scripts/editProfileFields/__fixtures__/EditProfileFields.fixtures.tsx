/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createReducer, configureStore } from "@reduxjs/toolkit";
import React, { ReactNode } from "react";
import { Provider } from "react-redux";
import { ProfileFieldsFixture } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileFieldMutability } from "@dashboard/userProfiles/types/UserProfiles.types";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { IUserProfilesState } from "@dashboard/userProfiles/state/UserProfiles.slice";

const mockFields = Object.fromEntries(ProfileFieldsFixture.mockProfileFields().map((field) => [field.apiName, field]));
const mockDisabledFields = Object.fromEntries(
    ProfileFieldsFixture.mockProfileFields(ProfileFieldMutability.NONE).map((field) => [field.apiName, field]),
);
const mockUserData = {
    2: {
        status: LoadStatus.SUCCESS,
        data: {
            text: "Sample input text",
            "text-multiline": "Sample textarea string",
            dropdown: "Option 2",
            checkbox: true,
            date: "2022-11-19T00:00:00+00:00",
            number: 1,
            tokens: ["Token 1", "Token 4"],
        },
    },
};

export class EditProfileFieldsFixture {
    public static mockFields: IUserProfilesState = {
        profileFieldApiNamesByParamHash: {
            [stableObjectHash({ filterEnabled: true })]: {
                status: LoadStatus.SUCCESS,
                data: Object.keys(mockFields),
            },
        },
        profileFieldsByApiName: mockFields,
        profileFieldsByUserID: mockUserData,
        deleteStatusByApiName: {},
    };

    public static mockDisabledFields: IUserProfilesState = {
        profileFieldApiNamesByParamHash: {
            [stableObjectHash({ filterEnabled: true })]: {
                status: LoadStatus.SUCCESS,
                data: Object.keys(mockDisabledFields),
            },
        },
        profileFieldsByApiName: mockDisabledFields,
        profileFieldsByUserID: mockUserData,
        deleteStatusByApiName: {},
    };

    public static createsMockProfileFieldsStore(state: object = {}) {
        const testReducer = createReducer({ ...state }, () => {});

        return configureStore({ reducer: testReducer });
    }

    public static createMockProfileFieldsProvider(children: ReactNode, state?: object) {
        return <Provider store={this.createsMockProfileFieldsStore(state)}>{children}</Provider>;
    }
}
