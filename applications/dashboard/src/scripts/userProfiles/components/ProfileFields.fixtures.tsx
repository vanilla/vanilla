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

export enum MockProfileFieldsFormat {
    API_NAME = "apiName",
    DATA_OBJECT = "object",
    DATA_ARRAY = "array",
}

/**
 * Utilities for testing User Profile Settings page.
 */
export class ProfileFieldsFixture {
    public static mockProfileFields(format?: MockProfileFieldsFormat) {
        const fields = Object.values(ProfileFieldFormType);
        if (format === MockProfileFieldsFormat.API_NAME) {
            return fields;
        }

        const fieldData = fields.map((apiName) => {
            const label = `${apiName
                .split("-")
                .map((val) => capitalize(val))
                .join(" ")} Field`;
            let dataType: ProfileFieldDataType;

            switch (apiName) {
                case ProfileFieldFormType.CHECKBOX:
                    dataType = ProfileFieldDataType.BOOLEAN;
                    break;

                case ProfileFieldFormType.DROPDOWN:
                case ProfileFieldFormType.TOKENS:
                    dataType = ProfileFieldDataType.STRING_MUL;
                    break;

                default:
                    dataType = ProfileFieldDataType[apiName] ?? ProfileFieldDataType.TEXT;
                    break;
            }

            return {
                apiName,
                label,
                dataType,
                formType: apiName,
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
            };
        });

        if (format === MockProfileFieldsFormat.DATA_OBJECT) {
            return Object.fromEntries(fieldData.map((field) => [field.apiName, field]));
        }

        return fieldData;
    }

    public static createMockProfileFieldsStore() {
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
                            data: this.mockProfileFields(MockProfileFieldsFormat.API_NAME),
                        },
                    },
                    profileFieldsByApiName: this.mockProfileFields(MockProfileFieldsFormat.DATA_OBJECT),
                    deleteStatusByApiName: {},
                },
            },
            () => {},
        );

        return configureStore({ reducer: testReducer });
    }

    public static createMockProfileFieldsProvider(children: ReactNode) {
        return <Provider store={this.createMockProfileFieldsStore()}>{children}</Provider>;
    }
}
