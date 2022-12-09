/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ProfileField, PatchUserProfileFieldsParams } from "@dashboard/userProfiles/types/UserProfiles.types";
import { hasPermission } from "@library/features/users/Permission";
import { JsonSchema } from "@vanilla/json-schema-forms";
import {
    ProfileFieldDataType,
    ProfileFieldMutability,
    ProfileFieldVisibility,
    ProfileFieldRegistrationOptions,
} from "@dashboard/userProfiles/types/UserProfiles.types";

/**
 * Converts ProfileFields to full JsonSchema object
 *
 * @param profileFieldConfigs
 */
export function mapProfileFieldsToSchema(profileFieldConfigs: ProfileField[]): JsonSchema {
    const userCanViewInternal = hasPermission("internalInfo.view");
    const userCanViewPersonal = hasPermission("personalInfo.view");
    const userCanEdit = hasPermission("users.edit");

    const convertDataType = (type) => {
        if (type === ProfileFieldDataType.TEXT || type === ProfileFieldDataType.DATE) {
            return "string";
        }
        if (type === ProfileFieldDataType.STRING_MUL || type === ProfileFieldDataType.NUMBER_MUL) {
            return "array";
        }
        return type;
    };

    const calcDisabled = (mutability) => {
        if (mutability === ProfileFieldMutability.NONE) {
            return true;
        }
        if (mutability === ProfileFieldMutability.RESTRICTED && !userCanEdit) {
            return true;
        }
        return false;
    };

    return {
        type: "object",
        properties: Object.fromEntries(
            profileFieldConfigs
                .filter((config) => {
                    if (!userCanViewInternal && config["visibility"] === ProfileFieldVisibility.INTERNAL) {
                        return false;
                    }
                    if (!userCanViewPersonal && config["visibility"] === ProfileFieldVisibility.PRIVATE) {
                        return false;
                    }
                    return config;
                })
                .map((config) => [
                    config["apiName"],
                    {
                        type: convertDataType(config["dataType"]),
                        minLength: 1,
                        disabled: calcDisabled(config["mutability"]),
                        visibility: config["visibility"],
                        "x-control": {
                            label: config["label"],
                            description: config["description"],
                            inputType: config["formType"],
                            choices: {
                                staticOptions: config["dropdownOptions"],
                            },
                        },
                    },
                ]),
        ),
        required: profileFieldConfigs
            .filter((config) => {
                return config["registrationOptions"] === ProfileFieldRegistrationOptions.REQUIRED;
            })
            .map((config) => config["apiName"]),
    };
}

/**
 * Takes UserMeta Custom Profile Field data and formats it to JsonSchema
 * The types make this confusing... JsonSchema can be partial??
 * Unflattens arrays to be IComboBoxOption
 *
 * @param schema - the form schema
 * @param userProfileFields - the object with all the user data
 */
export function mapUserProfileFieldValuesToSchema(
    schema: JsonSchema,
    userProfileFields: PatchUserProfileFieldsParams,
): JsonSchema {
    return Object.fromEntries(
        Object.keys(schema.properties).map((key) => {
            const field = userProfileFields[key];
            if (Array.isArray(field)) {
                const options: IComboBoxOption[] = Object.values(field).map((choice: string) => ({
                    value: choice,
                    label: choice,
                }));
                return [key, options];
            }
            return [key, field];
        }),
    );
}

function isIComboBoxOption(array: IComboBoxOption[] | any): boolean {
    return array[0] && array[0].hasOwnProperty("label") && array[0].hasOwnProperty("value");
}

/**
 * Takes dropdown options and flatens them for the API
 * Filters out values which have editing disabled as it will cause an API error even if the value sent is whats in the DB
 *
 * @param values - The form values
 * @param schema - Need to check the schema to see which properties has editing blocked
 */
export function formatValuesforAPI(
    values: PatchUserProfileFieldsParams,
    schema: JsonSchema,
): PatchUserProfileFieldsParams {
    const flattenedValues: PatchUserProfileFieldsParams = Object.fromEntries(
        Object.keys(values).map((key) => {
            if (values[key] && isIComboBoxOption(values[key])) {
                const flatOptions: string[] = values[key].map((option: IComboBoxOption) => `${option.label}`);
                return [key, flatOptions];
            }
            return [key, values[key]];
        }),
    );

    Object.keys(schema.properties).forEach((key) => {
        if (schema.properties[key]["disabled"]) delete flattenedValues[key];
    });

    return flattenedValues;
}
