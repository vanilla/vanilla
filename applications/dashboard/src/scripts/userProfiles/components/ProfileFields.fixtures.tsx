/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { Provider } from "react-redux";
import { createReducer, configureStore } from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import capitalize from "lodash/capitalize";
import {
    ProfileField,
    ProfileFieldDataType,
    ProfileFieldFormType,
    ProfileFieldMutability,
    ProfileFieldRegistrationOptions,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";

/**
 * Utilities for testing User Profile Settings page.
 */
export class ProfileFieldsFixture {
    public static mockProfileField(
        formType: ProfileFieldFormType,
        data: Partial<Omit<ProfileField, "formType">> = {},
    ): ProfileField {
        let dataType: ProfileFieldDataType;

        switch (formType) {
            case ProfileFieldFormType.CHECKBOX:
                dataType = ProfileFieldDataType.BOOLEAN;
                break;

            case ProfileFieldFormType.DROPDOWN:
            case ProfileFieldFormType.TOKENS:
                dataType = ProfileFieldDataType.STRING_MUL;
                break;

            default:
                dataType = ProfileFieldDataType[formType] ?? ProfileFieldDataType.TEXT;
                break;
        }

        const fakeApiName = data.apiName ?? formType;

        const label = `${fakeApiName
            .split("-")
            .map((val) => capitalize(val))
            .join(" ")} Field`;

        return {
            ...{
                apiName: fakeApiName,
                formType: formType,
                label,
                dataType,
                description: `Mock ${label} for testing purposes`,
                registrationOptions: ProfileFieldRegistrationOptions.OPTIONAL,
                visibility: ProfileFieldVisibility.PUBLIC,
                mutability: ProfileFieldMutability.ALL,
                displayOptions: {
                    userCards: false,
                    posts: false,
                },
                enabled: true,
                dropdownOptions:
                    dataType === ProfileFieldDataType.STRING_MUL
                        ? ["Option 1", "Option 2", "Option 3", "Option 4"]
                        : null,
            },
            ...data,
        };
    }

    public static mockProfileFields(mutability: ProfileFieldMutability = ProfileFieldMutability.ALL): ProfileField[] {
        const formTypes = Object.values(ProfileFieldFormType); //Use the form types as unique API names

        return formTypes.map((formType) => {
            return this.mockProfileField(formType, { mutability });
        });
    }

    public static createMockProfileFieldsStore(mockFields = this.mockProfileFields()) {
        const testReducer = createReducer(
            {
                config: {
                    configPatchesByID: {},
                    configsByLookupKey: {
                        [stableObjectHash(["redirectURL.profile", "redirectURL.message"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                "redirectURL.profile": "profile-url-test",
                                "redirectURL.message": "profile-message-test",
                            },
                        },
                        [stableObjectHash(["labs.customProfileFields"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                "labs.customProfileFields": true,
                            },
                        },
                    },
                },
                userProfiles: {
                    profileFieldApiNamesByParamHash: {
                        [stableObjectHash({})]: {
                            status: LoadStatus.SUCCESS,
                            data: mockFields.map((field) => field.apiName),
                        },
                    },
                    profileFieldsByApiName: Object.fromEntries(mockFields.map((field) => [field.apiName, field])),
                    deleteStatusByApiName: {},
                },
            },
            () => {},
        );

        return configureStore({ reducer: testReducer });
    }

    public static createMockProfileFieldsProvider(children: ReactNode, mockFields?: ProfileField[]) {
        return <Provider store={this.createMockProfileFieldsStore(mockFields)}>{children}</Provider>;
    }
}
