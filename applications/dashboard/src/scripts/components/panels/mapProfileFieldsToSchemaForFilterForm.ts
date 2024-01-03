/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ProfileField, ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { mapProfileFieldDropdownOptionsToComboBoxOptions } from "@library/editProfileFields/utils";

import { t } from "@vanilla/i18n";
import { JSONSchemaType, JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";

function createSchemaForProfileFieldConfig(profileFieldConfig: ProfileField): PartialSchemaDefinition<
    | string
    | number
    | boolean
    | {
          start?: string;
          end?: string;
      }
    | string[]
> {
    const formType = profileFieldConfig["formType"];

    switch (formType) {
        case ProfileFieldFormType.TEXT:
        case ProfileFieldFormType.TEXT_MULTILINE:
            return {
                type: "string",
                "x-control": {
                    label: profileFieldConfig["label"],
                    inputType: "textBox",
                },
            };

        case ProfileFieldFormType.NUMBER:
            return {
                type: "number",
                "x-control": {
                    label: profileFieldConfig["label"],
                    inputType: "textBox",
                    type: "number",
                },
            };
        case ProfileFieldFormType.CHECKBOX:
            return {
                type: "boolean",
                "x-control": {
                    label: profileFieldConfig["label"],
                    inputType: "dropDown",
                    type: "boolean",
                    choices: {
                        staticOptions: {
                            true: t("Yes"),
                            false: t("No"),
                        },
                    },
                },
            };
        case ProfileFieldFormType.DATE:
            return {
                type: "object",
                "x-control": {
                    label: profileFieldConfig["label"],
                    inputType: "dateRange",
                },
                properties: {
                    start: {
                        type: "string",
                        nullable: true,
                    },
                    end: {
                        type: "string",
                        nullable: true,
                    },
                },
                required: [],
            };

        case ProfileFieldFormType.DROPDOWN:
        case ProfileFieldFormType.TOKENS:
            // we show a tokens input, meaning you can select multiple values
            // selecting multiple values should be understood as an OR operation
            return {
                type: "array",
                "x-control": {
                    inputType: "tokens",
                    legend: profileFieldConfig["label"],
                    choices: {
                        staticOptions: profileFieldConfig.dropdownOptions
                            ? mapProfileFieldDropdownOptionsToComboBoxOptions(profileFieldConfig.dropdownOptions)
                            : null,
                    },
                },
                items: { type: "string" },
            };
    }
}

export default function mapProfileFieldsToSchemaForFilterForm(profileFieldConfigs: ProfileField[]): JsonSchema {
    return {
        type: "object",
        properties: Object.fromEntries(
            profileFieldConfigs.map((profileFieldConfig) => [
                profileFieldConfig.apiName,
                createSchemaForProfileFieldConfig(profileFieldConfig),
            ]),
        ) as JsonSchema["properties"],
        required: [],
    };
}
