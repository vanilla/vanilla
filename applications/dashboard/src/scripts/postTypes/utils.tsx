/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { IPickerOption } from "@library/json-schema-forms";
import { Icon } from "@vanilla/icons";
import { labelize } from "@vanilla/utils";

export const originalPostTypes = ["discussion", "question", "idea", "poll", "event"] as const;
export const formType: CreatableFieldFormType[] = [...Object.values(CreatableFieldFormType)] as const;
export const fieldVisibility: CreatableFieldVisibility[] = [...Object.values(CreatableFieldVisibility)] as const;

function arrayToOptions(array: Readonly<string[]>): IPickerOption[] {
    return array.map((item) => ({
        label: labelize(item),
        value: item,
    }));
}

export function originalPostTypeAsOptions(): IPickerOption[] {
    return arrayToOptions(originalPostTypes);
}

export function formTypeAsOptions(): IPickerOption[] {
    return arrayToOptions(formType).map((option) => {
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
            return <Icon icon={"new-discussion"} />;
        case "question":
            return <Icon icon={"new-question"} />;
        case "idea":
            return <Icon icon={"new-idea"} />;
        case "event":
            return <Icon icon={"new-event"} />;
        case "poll":
            return <Icon icon={"new-poll"} />;
        default:
            return null;
    }
}
