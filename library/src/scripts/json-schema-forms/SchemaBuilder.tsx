/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Schema } from "@cfworker/json-schema";
import { Select } from "./Select.types";
import {
    Condition,
    ICommonControl,
    IFormControl,
    IPickerOption,
    ISelectControl,
    ITextBoxControl,
    JsonSchema,
    PartialSchemaDefinition,
} from "./types";

export class SchemaFormBuilder {
    public constructor(protected inputs: JsonSchema["properties"] = {}, protected requiredProperties: string[] = []) {}

    private static headingCount = 0;

    public static create() {
        return new SchemaFormBuilder();
    }

    public custom(
        property: string,
        schema: PartialSchemaDefinition & { "x-control": ICommonControl | IFormControl | IFormControl[] },
    ) {
        this.inputs[property] = schema;
        return new PropertySpecificBuilder(property, this.inputs, this.requiredProperties);
    }

    public checkBox(property: string, label: string) {
        return this.custom(property, {
            type: "boolean",
            "x-control": {
                labelType: "standard",
                inputType: "checkBox",
                label,
            },
        });
    }

    public checkBoxRight(property: string, label: string, description: string | null, disabled?: boolean) {
        return this.custom(property, {
            type: "boolean",
            disabled,
            "x-control": {
                label,
                disabled,
                inputType: "checkBox",
                labelType: "wide",
                checkPosition: "right",
                description,
            },
        });
    }

    public toggle(property: string, label: string, description: string | null) {
        return this.custom(property, {
            type: "boolean",
            "x-control": {
                inputType: "toggle",
                label,
                description,
            },
        });
    }
    public radioGroup(property: string, label: string, description: string | null, options: IPickerOption[]) {
        return this.custom(property, {
            type: "string",
            "x-control": {
                inputType: "radio",
                label,
                ...this.convertOptionsToChoices(options),
                description,
            },
        });
    }

    private convertOptionsToChoices(options: IPickerOption[]) {
        return {
            choices: {
                staticOptions: options.reduce((acc, option) => {
                    acc[option.value] = option.label;
                    return acc;
                }, {}),
            },
            tooltipsPerOption: options
                .filter((opt) => opt.tooltip)
                .reduce((acc, option) => {
                    acc[option.value] = option.tooltip;
                    return acc;
                }, {}),
            notesPerOption: options
                .filter((opt) => opt.description)
                .reduce((acc, option) => {
                    acc[option.value] = option.description;
                    return acc;
                }, {}),
        };
    }

    public radioPicker(property: string, label: string, description: string | null, options: IPickerOption[]) {
        return this.custom(property, {
            "x-control": {
                inputType: "radioPicker",
                label,
                description,
                options,
            },
        });
    }

    public textBoxInternal(
        property: string,
        label: string,
        description: string | null,
        type: ITextBoxControl["type"],
        disabled?: boolean,
        pattern?: string,
    ) {
        return new TextBoxBuilder(
            this.custom(property, {
                type: "string",
                disabled,
                "x-control": {
                    inputType: "textBox",
                    label,
                    description,
                    type,
                    disabled,
                    pattern,
                },
            }),
        );
    }

    public textBox(property: string, label: string, description: string | null, disabled?: boolean, pattern?: string) {
        return this.textBoxInternal(property, label, description, "text", disabled, pattern);
    }

    public textArea(property: string, label: string, description: string | null, disabled?: boolean) {
        return this.textBoxInternal(property, label, description, "textarea", disabled);
    }

    public currency(property: string, label: string, description: string | null, disabled?: boolean) {
        return this.textBoxInternal(property, label, description, "currency", disabled);
    }

    public ratio(property: string, label: string, description: string | null, disabled?: boolean) {
        return this.textBoxInternal(property, label, description, "ratio", disabled);
    }

    public password(property: string, label: string, description: string | null, disabled?: boolean) {
        return this.textBoxInternal(property, label, description, "password", disabled);
    }

    public subHeading(label: string) {
        const count = SchemaFormBuilder.headingCount++;
        return this.custom(`schemaHeading_${count}`, {
            type: "null",
            "x-control": {
                labelType: "none",
                inputType: "subheading",
                label,
            },
        });
    }

    public staticText(label: ICommonControl["label"]) {
        return this.custom(`staticText`, {
            type: "null",
            "x-control": {
                labelType: "none",
                inputType: "staticText",
                label,
            },
        });
    }

    public dateRange(property: string, label: string, description: string | null) {
        return this.custom(property, {
            type: "object",
            properties: {
                start: {
                    type: "string",
                },
                end: {
                    type: "string",
                },
            },
            "x-control": {
                inputType: "dateRange",
                label,
                description,
            },
        });
    }

    public datePicker(property: string, label: string, description: string | null) {
        return this.custom(property, {
            type: "string",
            "x-control": {
                inputType: "datePicker",
                label,
                description,
            },
        });
    }

    public timeDuration(property: string, label: string, description: string | null) {
        return this.custom(property, {
            type: "string",
            "x-control": {
                inputType: "timeDuration",
                label,
                description,
            },
        });
    }

    public dropdown(
        property: string,
        label: ICommonControl["label"],
        description: string | null,
        options: IPickerOption[],
        disabled?: boolean,
    ) {
        return this.custom(property, {
            type: "string",
            disabled,
            "x-control": {
                inputType: "dropDown",
                label,
                description,
                ...this.convertOptionsToChoices(options),
            },
        });
    }

    public autoComplete(property: string, label: string, description: string | null, options: IPickerOption[]) {
        return this.custom(property, {
            type: "string",
            "x-control": {
                inputType: "dropDown",
                label,
                description,
                ...this.convertOptionsToChoices(options),
            },
        });
    }

    public selectLookup(
        property: string,
        label: string,
        description: string | null,
        lookup: Select.LookupApi,
        isMulti?: boolean,
        placeholder?: string,
    ) {
        const control: ISelectControl = {
            inputType: "select",
            label,
            description,
            optionsLookup: lookup,
            multiple: isMulti,
            placeholder: placeholder,
        };
        if (isMulti) {
            return this.custom(property, {
                type: "array",
                items: {
                    type: ["string", "number"],
                },
                "x-control": control,
            });
        } else {
            return this.custom(property, {
                type: ["string", "number"],
                "x-control": control,
            });
        }
    }

    public selectStatic(
        property: string,
        label: ICommonControl["label"],
        description: string | null,
        options: Select.Option[],
        isMulti?: boolean,
    ) {
        const control: ISelectControl = {
            inputType: "select",
            label,
            description,
            options: options,
            multiple: isMulti,
        };
        if (isMulti) {
            return this.custom(property, {
                type: "array",
                items: {
                    type: ["string", "number"],
                },
                "x-control": control,
            });
        } else {
            return this.custom(property, {
                type: ["string", "number"],
                "x-control": control,
            });
        }
    }

    public getSchema(): JsonSchema {
        return {
            type: "object",
            properties: this.inputs,
            required: this.requiredProperties,
        };
    }
}

class PropertySpecificBuilder<T extends ICommonControl = ICommonControl> extends SchemaFormBuilder {
    protected currentProperty: string;

    protected get currentPropertySchema(): Omit<PartialSchemaDefinition, "x-control"> & {
        "x-control": T;
    } & Schema {
        return this.inputs[this.currentProperty] as any;
    }

    public constructor(builder: PropertySpecificBuilder);
    public constructor(currentProperty: string, inputs: JsonSchema["properties"], requiredProperties: string[]);
    public constructor(
        public propertyOrBuilder: string | PropertySpecificBuilder,
        inputs: JsonSchema["properties"] = {},
        requiredProperties: string[] = [],
    ) {
        if (typeof propertyOrBuilder === "string") {
            super(inputs, requiredProperties);
            this.currentProperty = propertyOrBuilder;
        } else {
            super(propertyOrBuilder.inputs, propertyOrBuilder.requiredProperties);
            this.currentProperty = propertyOrBuilder.currentProperty;
        }
    }

    public required(): this {
        this.requiredProperties.push(this.currentProperty);
        return this;
    }

    public withDefault(value: any): this {
        this.currentPropertySchema.default = value;
        return this;
    }

    public withCondition(condition: Condition): this {
        const existingControl = this.currentPropertySchema["x-control"];
        if (Array.isArray(existingControl)) {
            throw new Error("You can't use withCondition on a property with multiple controls");
        }
        if (!existingControl.hasOwnProperty("conditions")) {
            existingControl["conditions"] = [];
        }
        const existingConditions = existingControl.conditions ?? [];
        existingConditions.push(condition);
        return this;
    }

    public withDescription(description: ICommonControl["description"]): this {
        this.currentPropertySchema["x-control"].description = description;
        return this;
    }

    public withTooltip(tooltip: ICommonControl["tooltip"]): this {
        this.currentPropertySchema["x-control"].tooltip = tooltip;
        return this;
    }

    public withLabelType(labelType: ICommonControl["labelType"]): this {
        this.currentPropertySchema["x-control"].labelType = labelType;
        return this;
    }

    public withControlParams(params: Partial<ICommonControl>): this {
        this.currentPropertySchema["x-control"] = {
            ...this.currentPropertySchema["x-control"],
            ...params,
        };
        return this;
    }

    public asNested() {
        this.currentPropertySchema["x-control"].isNested = true;
        return this;
    }

    public withoutBorder() {
        this.currentPropertySchema["x-control"].noBorder = true;
        return this;
    }
}

class TextBoxBuilder extends PropertySpecificBuilder<ITextBoxControl> {
    public withMaxLength(maxLength: number): this {
        this.currentPropertySchema.maxLength = maxLength;
        this.currentPropertySchema["x-control"].maxLength = maxLength;
        return this;
    }

    public withMinLength(minLength: number): this {
        this.currentPropertySchema.minLength = minLength;
        this.currentPropertySchema["x-control"].minLength = minLength;
        return this;
    }

    public withPattern(pattern: string): this {
        this.currentPropertySchema.pattern = pattern;
        this.currentPropertySchema["x-control"].pattern = pattern;
        return this;
    }
}

type NoInputType<T> = Omit<T, "inputType">;
