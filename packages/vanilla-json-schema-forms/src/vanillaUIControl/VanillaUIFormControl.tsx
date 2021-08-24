/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";
import { IControlProps } from "../types";
import { TextBox, AutoComplete, AutoCompleteLookupOptions, AutoCompleteOption, FormGroupContext } from "@vanilla/ui";
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
    const contextProps = vanillaUIContext.inputTypeProps[control.inputType] ?? {};
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
                >
                    {api && <AutoCompleteLookupOptions lookup={api} />}
                    {staticOptions &&
                        Object.entries(staticOptions).map(([value, label]) => (
                            <AutoCompleteOption key={value} value={value} label={String(label)} />
                        ))}
                </AutoComplete>
            );
            break;
        default:
            content = <div>{`Form inputType "${control.inputType}" is not supported.`}</div>;
            break;
    }

    return <>{content}</>;
}
