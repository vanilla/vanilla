/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { CreatableFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { IFormControl, JsonSchema, type ICheckBoxControl } from "@library/json-schema-forms";
import { labelize, notEmpty } from "@vanilla/utils";
import { spec } from "node:test/reporters";

/**
 * Get the POST url for a given post type
 */
export const getPostEndpointForPostType = (postType: PostType | null): string | null => {
    const baseTypeID = postType?.parentPostTypeID ?? postType?.postTypeID;
    switch (baseTypeID) {
        case "discussion":
            return "/discussions";
        case "question":
            return "/discussions/question";
        case "idea":
            return "/discussions/idea";
        default:
            return null;
    }
};

const getInputType = (formType: CreatableFieldFormType): IFormControl["inputType"] => {
    switch (formType) {
        case CreatableFieldFormType.TEXT:
        case CreatableFieldFormType.NUMBER:
        case CreatableFieldFormType.TEXT_MULTILINE:
            return "textBox";
        case CreatableFieldFormType.CHECKBOX:
            return "checkBox";
        case CreatableFieldFormType.DROPDOWN:
        case CreatableFieldFormType.TOKENS:
            return "select";
        case CreatableFieldFormType.DATE:
            return "datePicker";
    }
};

const makeControl = (postField: PostField): IFormControl => {
    const commonControl = {
        inputType: getInputType(postField.formType),
        label: postField.label,
        description: postField.description,
        ...(postField.formType === CreatableFieldFormType.TEXT_MULTILINE && { type: "textarea" }),
    };
    let specificControl = {};

    if ([CreatableFieldFormType.DROPDOWN, CreatableFieldFormType.TOKENS].includes(postField.formType)) {
        if (postField.hasOwnProperty("dropdownOptions")) {
            const choices = postField.dropdownOptions?.map((option) => ({
                label: labelize(option),
                value: option,
            }));
            specificControl = {
                options: choices,
                choices,
                multiple: postField.formType === CreatableFieldFormType.TOKENS,
            };
        }
    }

    if (commonControl.inputType === "checkBox") {
        specificControl = {
            checkPosition: "right",
        } as ICheckBoxControl;
    }

    return {
        ...commonControl,
        ...specificControl,
    } as IFormControl;
};

export const buildSchemaFromPostFields = (postFields: PostField[]): JsonSchema => {
    const required = postFields
        .map((postField) => (postField.isRequired ? postField.postFieldID : null))
        .filter(notEmpty);

    const properties = postFields.reduce((acc, postField) => {
        const newProperty = {
            type: postField.dataType.includes("[]") ? "array" : postField.dataType,
            visibility: postField.visibility,
            "x-control": makeControl(postField),
        };

        return {
            ...acc,
            [postField.postFieldID]: newProperty,
        };
    }, {});

    const schema: JsonSchema = {
        type: "object",
        properties,
        required,
    };

    return schema;
};
