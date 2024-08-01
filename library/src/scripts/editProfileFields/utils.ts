/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ProfileField,
    PatchUserProfileFieldsParams,
    UserProfileFields,
    ProfileFieldFormType,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { IFormControl, JsonSchema } from "@vanilla/json-schema-forms";
import {
    ProfileFieldDataType,
    ProfileFieldMutability,
    ProfileFieldRegistrationOptions,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { t } from "@vanilla/i18n";
import moment from "moment";

export function mapProfileFieldDropdownOptionsToComboBoxOptions(
    dropdownOptions: NonNullable<ProfileField["dropdownOptions"]>,
) {
    return Object.fromEntries(
        dropdownOptions.map((option: string | number) => {
            const value = option;
            const label = `${option}`;
            return [value, label];
        }),
    );
}

/**
 * Converts ProfileFields to full JsonSchema object
 *
 * @param profileFieldConfigs
 * @param hideDescriptions Wether to render descriptions after labels or no.
 * @returns Schema for profile form fields
 */
export function mapProfileFieldsToSchema(
    profileFieldConfigs: ProfileField[],
    options?: {
        hideDescriptions?: boolean;
        userCanEdit?: boolean;
    },
): JsonSchema {
    const hideDescriptions = options?.hideDescriptions ?? false;

    const userCanEdit = options?.userCanEdit ?? false;

    const mapProfileFieldToType = (field: ProfileField) => {
        const dataType = field.dataType;
        if (dataType === ProfileFieldDataType.TEXT || dataType === ProfileFieldDataType.DATE) {
            return "string";
        }
        if (dataType === ProfileFieldDataType.STRING_MUL || dataType === ProfileFieldDataType.NUMBER_MUL) {
            return "array";
        }

        return dataType;
    };

    function mapProfileFieldFormTypeToFormControlInputType(
        formType: ProfileField["formType"],
    ): IFormControl["inputType"] {
        switch (formType) {
            case ProfileFieldFormType.TEXT:
            case ProfileFieldFormType.NUMBER:
            case ProfileFieldFormType.TEXT_MULTILINE:
                return "textBox";
            case ProfileFieldFormType.CHECKBOX:
                return "checkBox";
            case ProfileFieldFormType.DROPDOWN:
                return "dropDown";
            case ProfileFieldFormType.TOKENS:
                return "tokens";
            case ProfileFieldFormType.DATE:
                return "datePicker";
        }
    }

    return {
        type: "object",
        properties: Object.fromEntries(
            profileFieldConfigs.map((config) => [
                config["apiName"],
                {
                    type: mapProfileFieldToType(config) as any, //fixme
                    minLength: config["registrationOptions"] === ProfileFieldRegistrationOptions.REQUIRED ? 1 : 0,
                    minItems: config["registrationOptions"] === ProfileFieldRegistrationOptions.REQUIRED ? 1 : 0,
                    disabled:
                        config["mutability"] === ProfileFieldMutability.NONE ||
                        (config["mutability"] === ProfileFieldMutability.RESTRICTED && !userCanEdit),
                    visibility: config["visibility"],
                    "x-control": {
                        ...(config["formType"] === ProfileFieldFormType.TOKENS
                            ? {
                                  legend: config["label"],
                              }
                            : {
                                  label: config["label"],
                              }),
                        description: !hideDescriptions ? config["description"] : undefined,
                        inputType: mapProfileFieldFormTypeToFormControlInputType(config["formType"]),
                        choices: {
                            staticOptions: config["dropdownOptions"]
                                ? mapProfileFieldDropdownOptionsToComboBoxOptions(config["dropdownOptions"])
                                : null,
                        },
                        type:
                            config["formType"] === ProfileFieldFormType.TEXT_MULTILINE
                                ? "textarea"
                                : config["formType"] === ProfileFieldFormType.NUMBER
                                ? "number"
                                : undefined,
                        multiple:
                            config["formType"] === ProfileFieldFormType.TOKENS && config["dropdownOptions"]
                                ? true
                                : undefined,

                        ...(config["visibility"] === ProfileFieldVisibility.INTERNAL && {
                            tooltip: t(
                                "This information will only be shown to users with permission to view internal info.",
                            ),
                            tooltipIcon: "profile-crown",
                        }),

                        ...(config["visibility"] === ProfileFieldVisibility.PRIVATE && {
                            tooltip: t("This is private information and will not be shared with other members."),
                            tooltipIcon: "profile-lock",
                        }),
                    } as IFormControl,
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
 * Takes a schema and the UserMeta Custom Profile Field, to provide data in the expected shape for the form.
 * Unflattens arrays to be IComboBoxOption
 *
 * @param schema - the form schema
 * @param userProfileFields - the object with all the user data
 */
export function mapUserProfileFieldsToFormValues(schema: JsonSchema, userProfileFields: UserProfileFields) {
    return Object.fromEntries(
        Object.entries(schema.properties).map(([key, val]: [string, object]) => {
            let value = userProfileFields[key];

            if (val["x-control"]["inputType"] === "datePicker" && value) {
                value = moment(value).format("YYYY-MM-DD");
            }

            return [key, value];
        }),
    );
}

// FIXME: https://higherlogic.atlassian.net/browse/VNLA-3088
// Once dates are stored correctly (without time zone) in the backend, this function can be removed.
export function formatDateStringIgnoringTimezone(value: string) {
    //removing "Z" from the end of ISO time format will just ignore UTC Date which coordinating timezones and will just use local instead
    if (!value) return "";
    return new Date(value).toISOString().slice(0, -1);
}

/**
 * Filters out values which have editing disabled as it will cause an API error even if the value sent is whats in the DB
 *
 * @param values - The form values
 * @param schema - Need to check the schema to see which properties has editing blocked
 */
export function formatValuesforAPI(
    values: PatchUserProfileFieldsParams,
    schema: JsonSchema,
): PatchUserProfileFieldsParams {
    const formattedValues: PatchUserProfileFieldsParams = Object.assign({ ...values });

    Object.keys(schema.properties).forEach((key) => {
        if (key in formattedValues && formattedValues[key] === undefined) {
            formattedValues[key] = null;
        }
        if (schema.properties[key]["disabled"]) delete formattedValues[key];
    });

    return formattedValues;
}

/**
 * Adjusts data received from API for proper presentation in FE
 *
 * @param initialUserProfileFields - Schema
 * @returns  Transformed data
 */
export const transformUserProfileFieldsData = (
    initialUserProfileFields: UserProfileFields,
    profileFields: ProfileField[],
): UserProfileFields => {
    const finalUserProfileFieldsData = { ...initialUserProfileFields };
    profileFields.forEach(({ apiName, dataType }) => {
        //some tweaks here until dates are the right format in BE, see comment for formatDateStringIgnoringTimezone() in its origin file
        if (dataType === ProfileFieldDataType.DATE) {
            finalUserProfileFieldsData[apiName] = finalUserProfileFieldsData[apiName]
                ? formatDateStringIgnoringTimezone(finalUserProfileFieldsData[apiName])
                : "";
        }
        //convert these into string as our Autocomplete expects strings as dropdown values
        if (dataType === ProfileFieldDataType.NUMBER_MUL) {
            finalUserProfileFieldsData[apiName] = (finalUserProfileFieldsData[apiName] ?? []).map((field) =>
                field.toString(),
            );
        }
    });
    return finalUserProfileFieldsData;
};
