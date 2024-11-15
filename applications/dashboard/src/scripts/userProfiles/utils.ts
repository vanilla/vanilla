/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ProfileFieldFormValues,
    ProfileField,
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldType,
    CreatableFieldMutability,
    CreatableFieldVisibility,
    ProfileFieldRegistrationOptions,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import pick from "lodash-es/pick";

const DATA_TYPE_AND_FORM_TYPE_MAP: { [index in `${CreatableFieldType}`]: Pick<ProfileField, "dataType" | "formType"> } =
    {
        [CreatableFieldType.TEXT_INPUT]: {
            dataType: CreatableFieldDataType.TEXT,
            formType: CreatableFieldFormType.TEXT,
        },
        [CreatableFieldType.TEXT_BOX]: {
            dataType: CreatableFieldDataType.TEXT,
            formType: CreatableFieldFormType.TEXT_MULTILINE,
        },
        [CreatableFieldType.SINGLE_SELECT_DROPDOWN]: {
            dataType: CreatableFieldDataType.TEXT,
            formType: CreatableFieldFormType.DROPDOWN,
        },
        [CreatableFieldType.SINGLE_CHECKBOX]: {
            dataType: CreatableFieldDataType.BOOLEAN,
            formType: CreatableFieldFormType.CHECKBOX,
        },
        [CreatableFieldType.MULTI_SELECT_DROPDOWN]: {
            dataType: CreatableFieldDataType.STRING_MUL,
            formType: CreatableFieldFormType.TOKENS,
        },
        [CreatableFieldType.DATE_PICKER]: {
            dataType: CreatableFieldDataType.DATE,
            formType: CreatableFieldFormType.DATE,
        },
        [CreatableFieldType.NUMERIC_INPUT]: {
            dataType: CreatableFieldDataType.NUMBER,
            formType: CreatableFieldFormType.NUMBER,
        },
        [CreatableFieldType.NUMERIC_DROPDOWN]: {
            dataType: CreatableFieldDataType.NUMBER_MUL,
            formType: CreatableFieldFormType.TOKENS,
        },
    };

export function getTypeOptions(dataType?: CreatableFieldDataType) {
    const allOptions = {
        [CreatableFieldType.TEXT_INPUT]: "Single Textbox",
        [CreatableFieldType.TEXT_BOX]: "Multi-line Textbox",
        [CreatableFieldType.SINGLE_CHECKBOX]: "Single Checkbox",
        [CreatableFieldType.SINGLE_SELECT_DROPDOWN]: "Single-select Dropdown",
        [CreatableFieldType.MULTI_SELECT_DROPDOWN]: "Multi-select Dropdown",
        [CreatableFieldType.DATE_PICKER]: "Date Picker",
        [CreatableFieldType.NUMERIC_INPUT]: "Numeric Input",
        [CreatableFieldType.NUMERIC_DROPDOWN]: "Numeric Dropdown",
    };

    if (dataType) {
        const compatibleProfileFieldTypes = Object.entries(DATA_TYPE_AND_FORM_TYPE_MAP)
            .filter(([_key, value]) => value.dataType === dataType)
            .map(([key]) => key);
        return pick(allOptions, compatibleProfileFieldTypes);
    }
    return allOptions;
}

export function mapProfileFieldToFormValues(profileField: ProfileField): ProfileFieldFormValues {
    const {
        dataType,
        formType,
        apiName,
        label,
        description,
        descriptionHtml,
        registrationOptions,
        visibility,
        mutability,
        displayOptions,
        dropdownOptions,
        enabled,
    } = profileField;

    const { userCards = false, posts = false, search = false } = displayOptions;

    const type = Object.entries(DATA_TYPE_AND_FORM_TYPE_MAP).find(([key, val]) => {
        return val.dataType === dataType && val.formType === formType;
    })![0] as CreatableFieldType;

    return {
        type,
        apiName,
        label,
        dropdownOptions: dropdownOptions && dropdownOptions.join("\n"),
        registrationOptions,
        description: {
            description,
            descriptionHtml,
        },
        visibility: {
            visibility,
            userCards,
            posts,
            search,
        },
        mutability,
        enabled,
    };
}

export function mapProfileFieldFormValuesToProfileField(formValues: ProfileFieldFormValues): ProfileField {
    const {
        apiName,
        label,
        description: { description, descriptionHtml = false },
        registrationOptions,
        mutability,
        visibility: { visibility, userCards, posts, search },
        type,
        enabled,
        dropdownOptions,
    } = formValues;

    const { dataType, formType } = DATA_TYPE_AND_FORM_TYPE_MAP[type];
    const requiresDropdownOptions = [
        CreatableFieldType.MULTI_SELECT_DROPDOWN,
        CreatableFieldType.NUMERIC_DROPDOWN,
        CreatableFieldType.SINGLE_SELECT_DROPDOWN,
    ].includes(type);

    let dropdownOptionArray;

    if (requiresDropdownOptions) {
        dropdownOptionArray = dropdownOptions?.split("\n");
        if (dataType === DATA_TYPE_AND_FORM_TYPE_MAP[CreatableFieldType.NUMERIC_DROPDOWN].dataType) {
            dropdownOptionArray = dropdownOptionArray.map((opt) => parseFloat(opt));
        }
    }

    return {
        dataType,
        formType,
        apiName,
        label,
        description,
        descriptionHtml,
        registrationOptions,
        visibility,
        mutability,
        enabled,
        displayOptions: {
            userCards,
            posts,
            search,
        },
        dropdownOptions: requiresDropdownOptions ? dropdownOptionArray : null,
    };
}

export const EMPTY_PROFILE_FIELD_CONFIGURATION: ProfileField = {
    apiName: "",
    label: "",
    description: "",
    descriptionHtml: false,
    dataType: CreatableFieldDataType.TEXT,
    formType: CreatableFieldFormType.TEXT,
    registrationOptions: ProfileFieldRegistrationOptions.OPTIONAL,
    visibility: CreatableFieldVisibility.PUBLIC,
    mutability: CreatableFieldMutability.ALL,
    displayOptions: { userCards: false, posts: false, search: true },
    enabled: true,
};
