/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export type IJsonSchema = (IObjectSchema | IIntegerSchema | IStringSchema | IBooleanSchema) & ICommonSchema;

interface ICommonControl {
    label?: string;
    description?: string;
    placeholder?: string;
    conditions?: ICondition[];
}

interface ICondition {
    fieldName: string;
    values: any[];
}

interface IChoices {
    staticOptions?: Record<string, string>;
    api?: {
        searchUrl: string;
        singleUrl: string;
        valueKey: string;
        labelKey: string;
    };
}

interface ICheckBoxControl extends ICommonControl {
    inputType: "checkBox" | "toggle";
}

interface IDropdownControl extends ICommonControl {
    inputType: "dropDown";
    choices: IChoices;
}

interface IRadioControl extends ICommonControl {
    inputType: "radio";
    choices: IChoices;
}

interface ITextBoxControl extends ICommonControl {
    inputType: "textBox";
    type?: string;
}

export type IFormControl = IDropdownControl | IRadioControl | ITextBoxControl | ICheckBoxControl;

interface ICommonSchema {
    default?: string;
    "x-control"?: IFormControl | IFormControl[];
}

interface IStringSchema extends ICommonSchema {
    type: "string";
    enum?: string[];
    minLength?: number;
    maxLength?: number;
}

interface IBooleanSchema extends ICommonSchema {
    type: "boolean";
}

interface IIntegerSchema extends ICommonSchema {
    type: "integer";
    multipleOf?: number;
    minimum?: number;
    maximum?: number;
    exclusiveMinimum?: number;
    exclusiveMaximum?: number;
}

interface IObjectSchema extends ICommonControl {
    type: "object";
    properties: Record<string, IJsonSchema>;
    required?: string[];
}
