/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { PropsWithChildren, ReactNode } from "react";
import { Provider } from "react-redux";
import {
    createReducer,
    configureStore,
    combineReducers,
    Reducer,
    ReducersMapObject,
    DeepPartial,
} from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import capitalize from "lodash-es/capitalize";
import {
    ProfileField,
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldMutability,
    ProfileFieldRegistrationOptions,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { IUserProfilesState } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { IConfigState } from "@library/config/configReducer";

export const mockProfileFieldsByUserID: Partial<IUserProfilesState["profileFieldsByUserID"]> = {
    2: {
        status: LoadStatus.SUCCESS,
        data: {
            text: "Sample input text",
            "text-multiline": "Sample textarea string",
            dropdown: "Option 2",
            checkbox: true,
            date: "2022-11-19",
            number: 1,
            tokens: ["Token 1", "Token 4"],
        },
    },
};

const mockConfigReducer = createReducer(
    {
        configPatchesByID: {},
        configsByLookupKey: {
            // TODO: check if these configs are rly necessary for these tests to pass
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
    } as Partial<IConfigState>,
    () => {},
);
/**
 * Utilities for testing Profile Fields and User Profiles
 */
export class ProfileFieldsFixtures {
    public static mockProfileField(
        formType: CreatableFieldFormType,
        data: DeepPartial<Omit<ProfileField, "formType">> = {},
    ): ProfileField {
        let dataType: CreatableFieldDataType;

        switch (formType) {
            case CreatableFieldFormType.CHECKBOX:
                dataType = CreatableFieldDataType.BOOLEAN;
                break;

            case CreatableFieldFormType.DROPDOWN:
                dataType = CreatableFieldDataType.TEXT;
                break;

            case CreatableFieldFormType.TOKENS:
                dataType = CreatableFieldDataType.STRING_MUL;
                break;

            case CreatableFieldFormType.DATE:
                dataType = CreatableFieldDataType.DATE;
                break;

            case CreatableFieldFormType.NUMBER:
                dataType = CreatableFieldDataType.NUMBER;
                break;

            default:
                dataType = CreatableFieldDataType.TEXT;
                break;
        }

        const fakeApiName = data.apiName ?? formType;

        const label =
            data.label ??
            `${fakeApiName
                .split("-")
                .map((val) => capitalize(val))
                .join(" ")} Field`;

        return {
            ...{
                apiName: fakeApiName,
                formType: formType,
                label,
                dataType,
                isCoreField: data.isCoreField ?? false,
                description: data.description ?? `Mock ${label} for testing purposes`,
                registrationOptions: data.registrationOptions ?? ProfileFieldRegistrationOptions.OPTIONAL,
                visibility: data.visibility ?? CreatableFieldVisibility.PUBLIC,
                mutability: data.mutability ?? CreatableFieldMutability.ALL,
                displayOptions: {
                    userCards: data.displayOptions?.userCards ?? false,
                    posts: data.displayOptions?.posts ?? false,
                    search: data.displayOptions?.search ?? false,
                },
                enabled: data.enabled ?? true,
                dropdownOptions:
                    data.dropdownOptions ??
                    [CreatableFieldFormType.TOKENS, CreatableFieldFormType.DROPDOWN].includes(formType)
                        ? formType === CreatableFieldFormType.TOKENS
                            ? ["Token 1", "Token 2", "Token 3", "Token 4"]
                            : ["Option 1", "Option 2", "Option 3", "Option 4"]
                        : null,
                sort: data.sort ?? 0,
            },
        };
    }

    public static mockProfileFields(
        mutability: CreatableFieldMutability = CreatableFieldMutability.ALL,
    ): ProfileField[] {
        const formTypes = Object.values(CreatableFieldFormType); //Use the form types as unique API names

        return formTypes.map((formType) => {
            return this.mockProfileField(formType, { mutability });
        });
    }

    public static mockDisabledFields() {
        return ProfileFieldsFixtures.mockProfileFields(CreatableFieldMutability.NONE);
    }

    static createMockProfileFieldsReducer(
        fieldsData = this.mockProfileFields(),
        profileFieldsByUserID = mockProfileFieldsByUserID,
    ) {
        return createReducer(
            {
                profileFieldsByUserID: profileFieldsByUserID,
                profileFieldApiNamesByParamHash: {
                    [stableObjectHash({ enabled: true })]: {
                        status: LoadStatus.SUCCESS,
                        data: fieldsData.filter((field) => field.enabled).map((field) => field.apiName),
                    },
                    [stableObjectHash({})]: {
                        status: LoadStatus.SUCCESS,
                        data: fieldsData.map((field) => field.apiName),
                    },
                },
                profileFieldsByApiName: Object.fromEntries(fieldsData.map((field) => [field.apiName, field])),
                deleteStatusByApiName: {},
            },
            () => {},
        );
    }

    public static createMockProfileFieldsProvider(options?: {
        profileFields?: ProfileField[];
        profileFieldsByUserID?: Partial<IUserProfilesState["profileFieldsByUserID"]>;
        extraReducers?: ReducersMapObject;
    }) {
        const mockStore = configureStore({
            reducer: combineReducers({
                config: mockConfigReducer,
                userProfiles: this.createMockProfileFieldsReducer(
                    options?.profileFields,
                    options?.profileFieldsByUserID,
                ),
                ...(options?.extraReducers ?? {}),
            }),
        });

        return function WrappedChildren(props: PropsWithChildren<{}>) {
            return <Provider store={mockStore}>{props.children}</Provider>;
        };
    }
}
