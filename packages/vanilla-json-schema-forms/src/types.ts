/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { JSONSchemaType } from "ajv";
import { ErrorObject } from "ajv/dist/core";
import { SomeJSONSchema } from "ajv/dist/types/json-schema";
import React from "react";

export type Path = Array<string | number>;

interface ICommonControl {
    label?: string;
    description?: string;
    placeholder?: string;
    conditions?: Condition[];
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

interface ICodeBoxControl extends ICommonControl {
    inputType: "codeBox";
    language?: string;
    jsonSchemaUri?: string;
}

export interface ITabsControl extends ICommonControl {
    inputType: "tabs";
    property: string;
    choices: IChoices;
}

export interface ISchemaTab {
    id: string;
    label: string;
    isDefault?: boolean;
}

export type IFormControl =
    | IDropdownControl
    | IRadioControl
    | ITextBoxControl
    | ICheckBoxControl
    | ICodeBoxControl
    | ITabsControl;

export type JsonSchema = Partial<SomeJSONSchema>;

export type Condition = { field: string; disable?: boolean } & JsonSchema;

export interface IBaseSchemaFormProps {
    path: Array<string | number>;
    schema: JsonSchema;
    rootSchema: JsonSchema;
    instance: any;
    rootInstance: any;
    validation?: IValidationResult;
}

export interface IForm {
    url: string;
    searchParams: Record<string, string>;
    submitButtonText?: string;
}

export interface IFormTab {
    tabID: string;
    label: string;
    contents: JSX.Element;
}

export interface ITabsProps extends IBaseSchemaFormProps {
    selectedTabID: string;
    onSelectTab(tabID: string): void;
    tabs: IFormTab[];
}

export interface IFormProps extends IBaseSchemaFormProps {
    form: IForm;
}

export interface ISectionProps extends IBaseSchemaFormProps {
    title: string;
}

export interface IControlGroupProps extends IBaseSchemaFormProps {
    controls: IFormControl[];
}

export interface IControlProps extends IBaseSchemaFormProps {
    control: IFormControl;
    required?: boolean;
    disabled?: boolean;
    onChange(instance: any): void;
}

export interface IValidationResult {
    isValid: boolean;
    errors?: ErrorObject[] | null;
}

export interface ISchemaRenderProps {
    FormControlGroup?(props: React.PropsWithChildren<IControlGroupProps>): JSX.Element | null;
    Form?(props: React.PropsWithChildren<IFormProps>): JSX.Element | null;
    FormSection?(props: React.PropsWithChildren<ISectionProps>): JSX.Element | null;
    FormTabs?(props: React.PropsWithChildren<ITabsProps>): JSX.Element | null;
    FormControl(props: React.PropsWithChildren<IControlProps>): JSX.Element | null;
}

export interface IPtrReference {
    path: Array<string | number>;
    ref: Path;
}
