/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import {
    ProfileField,
    PatchUserProfileFieldsParams,
    UserProfileFields,
    ProfileFieldFormType,
} from "@dashboard/userProfiles/types/UserProfiles.types";
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
 * @param dashboardMode If this is the case we need to map with little bit different wording  and not restrict with permissions.
 * @param hideDescriptions Wether to render descriptions after labels or no.
 * @returns Schema for profile form fields
 */
export function mapProfileFieldsToSchema(
    profileFieldConfigs: ProfileField[],
    dashboardMode?: boolean,
    hideDescriptions?: boolean,
): JsonSchema {
    const userCanViewInternal = hasPermission("internalInfo.view") || dashboardMode;
    const userCanViewPersonal = hasPermission("personalInfo.view") || dashboardMode;
    const userCanEdit = hasPermission("users.edit") || dashboardMode;

    const convertDataType = (dataType) => {
        if (dataType === ProfileFieldDataType.TEXT || dataType === ProfileFieldDataType.DATE) {
            return "string";
        }
        if (dataType === ProfileFieldDataType.STRING_MUL || dataType === ProfileFieldDataType.NUMBER_MUL) {
            return "array";
        }

        return dataType;
    };

    const convertFormTypeToDashboardMode = (type) => {
        switch (type) {
            case ProfileFieldFormType.TEXT:
            case ProfileFieldFormType.NUMBER:
            case ProfileFieldFormType.TEXT_MULTILINE:
                return "textBox";
            case ProfileFieldFormType.CHECKBOX:
                return "checkBox";
            case ProfileFieldFormType.DROPDOWN:
            case ProfileFieldFormType.TOKENS:
                return "dropDown";
            case ProfileFieldFormType.DATE:
                return "datePicker";
            default:
                return type;
        }
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
                        minLength: config["registrationOptions"] === ProfileFieldRegistrationOptions.REQUIRED ? 1 : 0,
                        minItems: config["registrationOptions"] === ProfileFieldRegistrationOptions.REQUIRED ? 1 : 0,
                        disabled: calcDisabled(config["mutability"]),
                        visibility: config["visibility"],
                        "x-control": {
                            label: config["label"],
                            description: !hideDescriptions ? config["description"] : undefined,
                            inputType: dashboardMode
                                ? convertFormTypeToDashboardMode(config["formType"])
                                : config["formType"],
                            choices: {
                                staticOptions:
                                    //we need to do this as even <Autocomplete/> expects options as array,
                                    //somehow it does not work, so TODO: we might want to investigate it
                                    dashboardMode && config["dropdownOptions"] && Array(config["dropdownOptions"])
                                        ? config["dropdownOptions"].map((option) => {
                                              return { value: option, label: option };
                                          })
                                        : config["dropdownOptions"],
                            },
                            type:
                                dashboardMode && config["formType"] === ProfileFieldFormType.TEXT_MULTILINE
                                    ? "textarea"
                                    : config["formType"] === ProfileFieldFormType.NUMBER
                                    ? "number"
                                    : undefined,
                            multiple:
                                dashboardMode &&
                                config["formType"] === ProfileFieldFormType.TOKENS &&
                                config["dropdownOptions"]
                                    ? true
                                    : undefined,
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
    userProfileFields: UserProfileFields,
): JsonSchema {
    return Object.fromEntries(
        Object.entries(schema.properties).map(([key, val]: [string, object]) => {
            let value = userProfileFields[key];

            if (val["x-control"]["inputType"] === "date" && value) {
                value = formatDateStringIgnoringTimezone(value as string);
            }

            if (Array.isArray(value)) {
                const options: IComboBoxOption[] = Object.values(value).map((choice: string) => ({
                    value: choice,
                    label: choice,
                }));
                return [key, options];
            }

            return [key, value];
        }),
    );
}

// FIXME: https://higherlogic.atlassian.net/browse/VNLA-3088
// Once dates are stored correctly (without time zone) in the backend, this function can be removed.
export function formatDateStringIgnoringTimezone(value: string) {
    return new Date(new Date(value).toISOString().slice(0, -1)).toDateString();
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
