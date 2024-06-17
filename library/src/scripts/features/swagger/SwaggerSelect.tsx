/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { inputClasses } from "@library/forms/inputStyles";
import { disabledInput } from "@library/styles/styleHelpers";
import { AutoComplete } from "@vanilla/ui";
import { List } from "immutable";
import React from "react";

interface IProps {
    className: string;
    title: string;
    multiple: boolean;
    disabled: boolean;
    value: string | string[];
    allowedValues: string[];
    allowEmptyValue: boolean;
    onChange: (newValue: any) => void;
}

/**
 * Override for the "Select" component from `SwaggerUI`. This one uses a token input/dropdown instead of a default browser <select />.
 */
export function SwaggerSelect(props: IProps) {
    return (
        <AutoComplete
            className={cx(inputClasses().inputContainer, props.disabled && css(disabledInput()))}
            size="small"
            disabled={props.disabled}
            value={props.value}
            placeholder={"--"}
            clear={true}
            multiple={props.multiple}
            onChange={props.onChange}
            options={props.allowedValues.map((value) => {
                return {
                    label: value,
                    value,
                };
            })}
        />
    );
}

function extractListArray(value: string | List<string>): string | string[] {
    if (List.isList(value)) {
        return (value as List<string>).toArray();
    } else {
        return value as string;
    }
}
