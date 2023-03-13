/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ProfileFieldFormValues,
    ProfileField,
    ProfileFieldDataType,
    ProfileFieldFormType,
    ProfileFieldType,
    ProfileFieldMutability,
    ProfileFieldVisibility,
    ProfileFieldRegistrationOptions,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import pick from "lodash/pick";

const DATA_TYPE_AND_FORM_TYPE_MAP: { [index in `${ProfileFieldType}`]: Pick<ProfileField, "dataType" | "formType"> } = {
    [ProfileFieldType.TEXT_INPUT]: {
        dataType: ProfileFieldDataType.TEXT,
        formType: ProfileFieldFormType.TEXT,
    },
    [ProfileFieldType.TEXT_BOX]: {
        dataType: ProfileFieldDataType.TEXT,
        formType: ProfileFieldFormType.TEXT_MULTILINE,
    },
    [ProfileFieldType.SINGLE_SELECT_DROPDOWN]: {
        dataType: ProfileFieldDataType.TEXT,
        formType: ProfileFieldFormType.DROPDOWN,
    },
    [ProfileFieldType.SINGLE_CHECKBOX]: {
        dataType: ProfileFieldDataType.BOOLEAN,
        formType: ProfileFieldFormType.CHECKBOX,
    },
    [ProfileFieldType.MULTI_SELECT_DROPDOWN]: {
        dataType: ProfileFieldDataType.STRING_MUL,
        formType: ProfileFieldFormType.TOKENS,
    },
    [ProfileFieldType.DATE_PICKER]: {
        dataType: ProfileFieldDataType.DATE,
        formType: ProfileFieldFormType.DATE,
    },
    [ProfileFieldType.NUMERIC_INPUT]: {
        dataType: ProfileFieldDataType.NUMBER,
        formType: ProfileFieldFormType.NUMBER,
    },
    [ProfileFieldType.NUMERIC_DROPDOWN]: {
        dataType: ProfileFieldDataType.NUMBER_MUL,
        formType: ProfileFieldFormType.TOKENS,
    },
};

export function getTypeOptions(dataType?: ProfileFieldDataType) {
    const allOptions = {
        [ProfileFieldType.TEXT_INPUT]: "Single Textbox",
        [ProfileFieldType.TEXT_BOX]: "Multi-line Textbox",
        [ProfileFieldType.SINGLE_CHECKBOX]: "Single Checkbox",
        [ProfileFieldType.SINGLE_SELECT_DROPDOWN]: "Single-select Dropdown",
        [ProfileFieldType.MULTI_SELECT_DROPDOWN]: "Multi-select Dropdown",
        [ProfileFieldType.DATE_PICKER]: "Date Picker",
        [ProfileFieldType.NUMERIC_INPUT]: "Numeric Input",
        [ProfileFieldType.NUMERIC_DROPDOWN]: "Numeric Dropdown",
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
        registrationOptions,
        visibility,
        mutability,
        displayOptions,
        dropdownOptions,
        enabled,
    } = profileField;

    const { userCards = false, posts = false } = displayOptions;

    const type = Object.entries(DATA_TYPE_AND_FORM_TYPE_MAP).find(([key, val]) => {
        return val.dataType === dataType && val.formType === formType;
    })![0] as ProfileFieldType;

    return {
        type,
        apiName,
        label,
        description,
        dropdownOptions: dropdownOptions && dropdownOptions.join("\n"),
        registrationOptions,
        visibility: {
            visibility,
            userCards,
            posts,
        },
        mutability,
        enabled,
    };
}

export function mapProfileFieldFormValuesToProfileField(formValues: ProfileFieldFormValues): ProfileField {
    const {
        apiName,
        label,
        description,
        registrationOptions,
        mutability,
        visibility: { visibility, userCards, posts },
        type,
        enabled,
        dropdownOptions,
    } = formValues;

    const { dataType, formType } = DATA_TYPE_AND_FORM_TYPE_MAP[type];
    const requiresDropdownOptions = [
        ProfileFieldType.MULTI_SELECT_DROPDOWN,
        ProfileFieldType.NUMERIC_DROPDOWN,
        ProfileFieldType.SINGLE_SELECT_DROPDOWN,
    ].includes(type);

    let dropdownOptionArray;

    if (requiresDropdownOptions) {
        dropdownOptionArray = dropdownOptions?.split("\n");
        if (dataType === DATA_TYPE_AND_FORM_TYPE_MAP[ProfileFieldType.NUMERIC_DROPDOWN].dataType) {
            dropdownOptionArray = dropdownOptionArray.map((opt) => parseFloat(opt));
        }
    }

    return {
        dataType,
        formType,
        apiName,
        label,
        description,
        registrationOptions,
        visibility,
        mutability,
        enabled,
        displayOptions: {
            userCards,
            posts,
        },
        dropdownOptions: requiresDropdownOptions ? dropdownOptionArray : null,
    };
}

export const EMPTY_PROFILE_FIELD_CONFIGURATION: ProfileField = {
    apiName: "",
    label: "",
    description: "",
    dataType: ProfileFieldDataType.TEXT,
    formType: ProfileFieldFormType.TEXT,
    registrationOptions: ProfileFieldRegistrationOptions.OPTIONAL,
    visibility: ProfileFieldVisibility.PUBLIC,
    mutability: ProfileFieldMutability.ALL,
    displayOptions: { userCards: false, posts: false },
    enabled: true,
};
