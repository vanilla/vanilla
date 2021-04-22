/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardCodeEditor } from "@dashboard/forms/DashboardCodeEditor";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { DashboardSelectLookup } from "@dashboard/forms/DashboardSelectLookup";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { useUniqueID } from "@library/utility/idUtils";
import { IFormControl, JsonSchema } from "@vanilla/json-schema-forms";
import React from "react";

interface IProps {
    formControl: IFormControl;
    schema: JsonSchema;
    value: any;
    onChange: (value: any) => void;
    isRequired?: boolean;
}

export function WidgetFormControl(props: IProps) {
    const { formControl, schema, onChange } = props;
    const value = props.value ?? schema.default;
    const inputName = useUniqueID("input");

    switch (formControl.inputType) {
        case "textBox":
            const isMultiline = formControl.type === "textarea";
            const typeIsNumber = formControl.type === "number";
            const typeIsUrl = formControl.type === "url";
            const type = typeIsNumber ? "number" : typeIsUrl ? "url" : "text";
            return (
                <DashboardInput
                    inputProps={{
                        value: value ?? "",
                        onChange: (event) => onChange(event.target.value),
                        maxLength: schema.type === "string" ? schema.maxLength : undefined,
                        type: !isMultiline ? type : undefined,
                        placeholder: formControl.placeholder,
                        multiline: isMultiline ? true : false,
                    }}
                    multiLineProps={
                        isMultiline
                            ? {
                                  rows: 4,
                              }
                            : undefined
                    }
                />
            );
        case "codeBox":
            return (
                <DashboardCodeEditor
                    value={value}
                    onChange={onChange}
                    language={formControl.language || "text/html"}
                    jsonSchemaUri={formControl.jsonSchemaUri}
                />
            );
        case "radio":
            return (
                <DashboardRadioGroup value={value} onChange={onChange}>
                    {Object.entries(formControl.choices.staticOptions ?? []).map(
                        ([optionValue, label]: [string, string]) => (
                            <DashboardRadioButton
                                name={inputName}
                                key={optionValue}
                                label={label}
                                value={optionValue}
                            />
                        ),
                    )}
                </DashboardRadioGroup>
            );
        case "dropDown":
            const { api, staticOptions } = formControl.choices;
            if (api) {
                return (
                    <DashboardSelectLookup
                        isClearable={!props.isRequired}
                        placeholder={formControl.placeholder || undefined}
                        value={value}
                        onChange={(option) => onChange(option?.value)}
                        api={api}
                    />
                );
            }
            const options = staticOptions
                ? Object.entries(staticOptions).map(([value, label]: [string, string]) => ({
                      value,
                      label,
                  }))
                : [];
            return (
                <DashboardSelect
                    isClearable={!props.isRequired}
                    value={options.find((opt) => {
                        let valueCompare: any = opt.value;
                        if (valueCompare === "true") {
                            valueCompare = true;
                        } else if (valueCompare === "false") {
                            valueCompare = false;
                        }
                        return valueCompare == value;
                    })}
                    placeholder={formControl.placeholder || undefined}
                    onChange={(option) => onChange(option?.value)}
                    options={options}
                />
            );
        case "checkBox":
        case "toggle":
            return <DashboardToggle checked={value} onChange={onChange} />;
        default:
            return <div>{(formControl as any).inputType} is not supported</div>;
    }
}
