/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createReducer, configureStore } from "@reduxjs/toolkit";
import React, { ReactNode } from "react";
import { Provider } from "react-redux";
import {
    MockProfileFieldsFormat,
    ProfileFieldsFixture,
} from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { ProfileFieldMutability } from "@dashboard/userProfiles/types/UserProfiles.types";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";

const mockFields = ProfileFieldsFixture.mockProfileFields(MockProfileFieldsFormat.DATA_OBJECT);
const mockDisabledFields = ProfileFieldsFixture.mockProfileFields(
    MockProfileFieldsFormat.DATA_OBJECT,
    ProfileFieldMutability.NONE,
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
    public static mockFields = {
        profileFieldApiNamesByParamHash: {
            [stableObjectHash({})]: {
                status: LoadStatus.SUCCESS,
                data: ProfileFieldsFixture.mockProfileFields(MockProfileFieldsFormat.API_NAME),
            },
        },
        profileFieldsByApiName: mockFields,
        profileFieldsByUserID: mockUserData,
    };

    public static mockDisabledFields = {
        profileFieldApiNamesByParamHash: {
            [stableObjectHash({})]: {
                status: LoadStatus.SUCCESS,
                data: ProfileFieldsFixture.mockProfileFields(MockProfileFieldsFormat.API_NAME),
            },
        },
        profileFieldsByApiName: mockDisabledFields,
        profileFieldsByUserID: mockUserData,
    };

    public static createsMockProfileFieldsStore(state: object = {}) {
        const testReducer = createReducer({ ...state }, () => {});

        return configureStore({ reducer: testReducer });
    }

    public static createMockProfileFieldsProvider(children: ReactNode, state?: object) {
        return <Provider store={this.createsMockProfileFieldsStore(state)}>{children}</Provider>;
    }
}
