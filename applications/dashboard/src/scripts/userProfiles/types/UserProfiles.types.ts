/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

export enum ProfileFieldVisibility {
    PUBLIC = "public",
    PRIVATE = "private",
    INTERNAL = "internal",
}

export enum ProfileFieldMutability {
    ALL = "all",
    RESTRICTED = "restricted",
    NONE = "none",
}

export enum ProfileFieldType {
    TEXT_INPUT = "textInput",
    TEXT_BOX = "textBox",
    SINGLE_CHECKBOX = "singleCheckbox",
    SINGLE_SELECT_DROPDOWN = "singleSelectDropdown",
    MULTI_SELECT_DROPDOWN = "multiSelectDropdown",
    DATE_PICKER = "datePicker",
    NUMERIC_INPUT = "numericInput",
    NUMERIC_DROPDOWN = "numericDropdown",
}

export interface IProfileFieldDisplayOptions {
    profiles: boolean;
    userCards: boolean;
    posts: boolean;
}

export type ProfileField = {
    apiName: string;
    label: string;
    description: string;
    required: boolean;
    visibility: ProfileFieldVisibility;
    mutability: ProfileFieldMutability;
    displayOptions: IProfileFieldDisplayOptions;
    dataType: ProfileFieldDataType;
    formType: ProfileFieldFormType;
};

// The form structure differs from the actual object's structure: some fields are nested in groups with others
export type ProfileFieldFormValues = Pick<ProfileField, "apiName" | "label" | "description"> & {
    visibility: {
        visibility: ProfileField["visibility"];
    } & ProfileField["displayOptions"];
} & {
    editing: {
        mutability: ProfileField["mutability"];
        required: ProfileField["required"];
    };
} & {
    type: ProfileFieldType; //there are utils to map the type selected in the form to valid combination dataType and formType.
};

export enum ProfileFieldDataType {
    TEXT = "text",
    BOOLEAN = "boolean",
    DATE = "date",
    NUMBER = "number",
    STRING_MUL = "string[]",
    NUMBER_MUL = "number[]",
}

export enum ProfileFieldFormType {
    TEXT = "text",
    TEXT_MULTILINE = "text-multiline",
    DROPDOWN = "dropdown",
    CHECKBOX = "checkbox",
    DATE = "date",
    NUMBER = "number",
    TOKENS = "tokens",
}

export type FetchProfileFieldsParams = {};

export type PostProfileFieldParams = ProfileField;

export type PatchProfileFieldParams = Omit<ProfileField, "dataType">;
