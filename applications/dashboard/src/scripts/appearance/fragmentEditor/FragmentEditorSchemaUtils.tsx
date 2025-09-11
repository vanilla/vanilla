/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { Schema } from "@cfworker/json-schema";
import type { PartialSchemaDefinition, IFormControl, Select } from "@library/json-schema-forms";

export interface ICustomField {
    apiName: string;
    label: string;
    controlType: FragmentControlType;
    choices?: string;
    required: boolean;
}
export function customFieldToSchema(field: ICustomField): PartialSchemaDefinition {
    const controlTypeOption = getControlTypeOptions().find((option) => option.value === field.controlType)!;
    const enumVal =
        field.choices &&
        [FragmentControlType.SelectMulti, FragmentControlType.SelectSingle].includes(field.controlType as any)
            ? choicesStringToArray(field.choices).map((choice) => choice.value)
            : undefined;
    return {
        type: controlTypeOption.schemaType,
        enum: controlTypeOption.schemaType === "string" ? enumVal : undefined,
        ...(controlTypeOption.schemaType === "array"
            ? {
                  items: {
                      type: "string",
                      enum: enumVal,
                  },
              }
            : {}),
        "x-control": {
            label: field.label,
            ...controlTypeOption.control,
            options: field.choices ? choicesStringToArray(field.choices) : undefined,
        } as IFormControl,
    };
}
export function choicesStringToArray(choices: string): Select.Option[] {
    return (
        choices
            ?.split("\n")
            .map((choice) => choice.trim())
            .filter((choice) => !!choice)
            .map((choice) => ({
                label: choice,
                value: choice,
            })) ?? []
    );
}
export function schemaToCustomField(
    apiName: string,
    schema: PartialSchemaDefinition,
    required: boolean,
): ICustomField | null {
    const control = schema["x-control"] as IFormControl;
    if (!control) {
        return null;
    }

    const controlTypeOption = getControlTypeOptions().find((option) => {
        for (const [optionKey, optionValue] of Object.entries(option.control)) {
            if (control[optionKey] !== optionValue) {
                return false;
            }
        }
        return true;
    });

    if (!controlTypeOption) {
        return null;
    }

    return {
        apiName,
        label: control.label as string,
        controlType: controlTypeOption.value,
        choices: "options" in control ? control.options?.map((opt) => opt.value).join("\n") ?? "" : "",
        required,
    };
}

export const FragmentControlType = {
    Text: "Text",
    TextMulti: "TextMulti",
    SelectSingle: "SelectSingle",
    SelectMulti: "SelectMulti",
    CheckBox: "CheckBox",
    Number: "Number",
    Image: "Image",
} as const;
export type FragmentControlType = (typeof FragmentControlType)[keyof typeof FragmentControlType];
export function getControlTypeOptions(): Array<{
    value: FragmentControlType;
    schemaType: PartialSchemaDefinition["type"];
    label: string;
    control: IFormControl;
}> {
    return [
        {
            value: "Text",
            label: "Text",
            schemaType: "string",
            control: {
                inputType: "textBox",
                type: "text",
            },
        },
        {
            value: "TextMulti",
            label: "Text Multiline",
            schemaType: "string",
            control: {
                inputType: "textBox",
                type: "textarea",
            },
        },
        {
            value: "SelectSingle",
            label: "Single-select Dropdown",
            schemaType: "string",
            control: {
                inputType: "select",
                multiple: false,
            },
        },
        {
            value: "SelectMulti",
            label: "Multi-select Dropdown",
            schemaType: "array",
            control: {
                inputType: "select",
                multiple: true,
            },
        },
        {
            value: "CheckBox",
            label: "Checkbox",
            schemaType: "boolean",
            control: {
                inputType: "checkBox",
            },
        },
        {
            value: "Number",
            label: "Number",
            schemaType: "integer",
            control: {
                inputType: "textBox",
                type: "number",
            },
        },
        {
            value: "Image",
            label: "Image",
            schemaType: "string",
            control: {
                inputType: "upload",
            },
        },
    ];
}
