/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useState } from "react";
import { t } from "@vanilla/i18n";
import { Tokens } from "@library/forms/select/Tokens";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { tokensClasses } from "@library/forms/select/tokensStyles";

interface IProps {
    fieldName: string;
    initialValue: IComboBoxOption[];
    options: Record<string, string>;
    label: string;
    description?: string;
}

export function TokensInputInLegacyForm(props: IProps) {
    const { label, fieldName, description } = props;

    //initial value might be a json from the back end, so we need to do a bit of validation/format here
    const [values, setValues] = useState<IComboBoxOption[]>(() => {
        return getValidInitialState(props.initialValue);
    });

    const options = useMemo<IComboBoxOption[]>(() => {
        //options with value/label
        if (Object.keys(props.options).length) {
            return Object.entries(props.options).map(([key, value]) => {
                return {
                    value: key,
                    label: value,
                };
            });
        }
        return [];
    }, [props.options]);

    return (
        <div className={tokensClasses().containerLegacyForm}>
            <Tokens
                label={t(label ?? "")}
                placeholder={t("Select...")}
                options={options}
                onChange={(newValues) => setValues(newValues)}
                value={values ?? []}
                fieldName={fieldName}
                labelNote={description}
            />
        </div>
    );
}

function getValidInitialState(initialValueFromProps: IComboBoxOption[] | string) {
    let validInitialValue: IComboBoxOption[] = [];
    try {
        const validData =
            typeof initialValueFromProps === "string" ? JSON.parse(initialValueFromProps) : initialValueFromProps;
        validInitialValue = validData;
    } catch (e) {
        validInitialValue = [];
    }
    return validInitialValue;
}
