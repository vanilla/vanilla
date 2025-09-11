/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";
import { IControlProps } from "../types";
import { TextBox, AutoComplete, AutoCompleteLookupOptions, FormGroupContext, type ILookupApi } from "@vanilla/ui";
import { useVanillaUIFormControlContext } from "./VanillaUIFormControlContext";

interface IProps extends IControlProps {}

export function VanillaUIFormControl(props: IProps) {
    const { control } = props;
    let content: React.ReactNode = null;
    const vanillaUIContext = useVanillaUIFormControlContext();
    const { inputID, labelID } = useContext(FormGroupContext);
    const commonProps = {
        id: inputID,
        "aria-labelledby": labelID,
    };
    const commonContextProps = vanillaUIContext.commonInputProps;
    const contextInputType = control.inputType === "select" ? "dropDown" : control.inputType;
    const contextProps = vanillaUIContext.inputTypeProps[contextInputType] ?? {};
    switch (control.inputType) {
        case "textBox":
            content = (
                <TextBox
                    {...commonProps}
                    {...commonContextProps}
                    {...contextProps}
                    disabled={props.disabled}
                    placeholder={props.control.placeholder}
                    value={props.instance}
                    onChange={(e) => {
                        props.onChange(e.target.value);
                    }}
                />
            );
            break;
        case "select":
            content = (
                <AutoComplete
                    {...commonProps}
                    {...commonContextProps}
                    {...contextProps}
                    value={props.instance}
                    placeholder={control.placeholder}
                    onChange={(newValue) => {
                        props.onChange(newValue);
                    }}
                    disabled={props.disabled}
                    optionProvider={
                        control.optionsLookup ? (
                            <AutoCompleteLookupOptions lookup={control.optionsLookup as ILookupApi} />
                        ) : undefined
                    }
                    options={
                        control.options
                            ? control.options.map((option) => ({
                                  key: option.value ?? option.label,
                                  value: option.value ?? option.label,
                                  label: option.label,
                              }))
                            : undefined
                    }
                />
            );
            break;
        case "dropDown":
            const { api, staticOptions } = control.choices;
            content = (
                <AutoComplete
                    {...commonProps}
                    {...commonContextProps}
                    {...contextProps}
                    value={props.instance}
                    placeholder={control.placeholder}
                    onChange={(newValue) => {
                        props.onChange(newValue);
                    }}
                    disabled={props.disabled}
                    optionProvider={api ? <AutoCompleteLookupOptions lookup={api} /> : undefined}
                    options={
                        staticOptions
                            ? Object.entries(staticOptions).map(([value, label]) => ({
                                  key: value,
                                  value,
                                  label: String(label),
                              }))
                            : undefined
                    }
                />
            );
            break;
        default:
            content = <div>{`Form inputType "${control.inputType}" is not supported.`}</div>;
            break;
    }

    return <>{content}</>;
}
