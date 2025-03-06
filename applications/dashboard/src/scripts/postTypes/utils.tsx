/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostType } from "@dashboard/postTypes/postType.types";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { IPickerOption } from "@library/json-schema-forms";
import { getMeta } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { labelize } from "@vanilla/utils";

export const originalPostTypes = ["discussion", "question", "idea", "event"] as const;
const enabledPostTypes = getMeta("postTypes", originalPostTypes);
export const formType: CreatableFieldFormType[] = [...Object.values(CreatableFieldFormType)] as const;
export const fieldVisibility: CreatableFieldVisibility[] = [...Object.values(CreatableFieldVisibility)] as const;

const TEXT_FORM_TYPES = [CreatableFieldFormType.TEXT, CreatableFieldFormType.TEXT_MULTILINE];
const SELECT_FORM_TYPES = [CreatableFieldFormType.DROPDOWN, CreatableFieldFormType.TOKENS];
const INCOMPATIBLE_FORM_TYPES = [
    CreatableFieldFormType.DATE,
    CreatableFieldFormType.NUMBER,
    CreatableFieldFormType.CHECKBOX,
];

function arrayToOptions(array: Readonly<string[]>): IPickerOption[] {
    return array.map((item) => ({
        label: labelize(item),
        value: item,
    }));
}

export function originalPostTypeAsOptions(): IPickerOption[] {
    return arrayToOptions(originalPostTypes.filter((postType) => enabledPostTypes.includes(postType)));
}

export function formTypeAsOptions(initialType?: CreatableFieldFormType): IPickerOption[] {
    let formArray = formType;
    if (initialType) {
        if (TEXT_FORM_TYPES.includes(initialType)) {
            formArray = TEXT_FORM_TYPES;
        }
        if (SELECT_FORM_TYPES.includes(initialType)) {
            formArray = SELECT_FORM_TYPES;
        }
        if (INCOMPATIBLE_FORM_TYPES.includes(initialType)) {
            formArray = [];
        }
    }
    return arrayToOptions(formArray).map((option) => {
        if (option.value === CreatableFieldFormType.TOKENS) {
            return {
                ...option,
                label: "Multi-select Dropdown",
            };
        }
        if (option.value === CreatableFieldFormType.DROPDOWN) {
            return {
                ...option,
                label: "Single-select Dropdown",
            };
        }
        return option;
    });
}

export function fieldVisibilityAsOptions(): IPickerOption[] {
    return arrayToOptions(fieldVisibility);
}

export function mapFormTypeToDataType(formType: CreatableFieldFormType): CreatableFieldDataType {
    switch (formType) {
        case CreatableFieldFormType.TEXT:
        case CreatableFieldFormType.TEXT_MULTILINE:
            return CreatableFieldDataType.TEXT;
        case CreatableFieldFormType.NUMBER:
            return CreatableFieldDataType.NUMBER;
        case CreatableFieldFormType.DATE:
            return CreatableFieldDataType.DATE;
        case CreatableFieldFormType.CHECKBOX:
            return CreatableFieldDataType.BOOLEAN;
        case CreatableFieldFormType.DROPDOWN:
            return CreatableFieldDataType.TEXT;
        case CreatableFieldFormType.TOKENS:
            return CreatableFieldDataType.STRING_MUL;
        default:
            return CreatableFieldDataType.TEXT;
    }
}

export function getIconForPostType(postType: PostType["postTypeID"]) {
    switch (postType) {
        case "discussion":
            return <Icon icon={"create-discussion"} />;
        case "question":
            return <Icon icon={"create-question"} />;
        case "idea":
            return <Icon icon={"create-idea"} />;
        case "event":
            return <Icon icon={"create-event"} />;
        case "poll":
            return <Icon icon={"create-poll"} />;
        default:
            return null;
    }
}
