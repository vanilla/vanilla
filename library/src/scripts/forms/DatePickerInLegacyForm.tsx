/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import InputBlock from "@library/forms/InputBlock";
import DatePicker from "@library/forms/DatePicker";

interface IProps {
    fieldName: string;
    initialValue: string;
    label: string;
    description?: string;
}

export function DatePickerInLegacyForm(props: IProps) {
    const { fieldName, initialValue, label, description } = props;

    const [value, setValue] = useState<string>(initialValue);

    return (
        <InputBlock label={label} labelNote={description}>
            <DatePicker value={value} onChange={(newValue) => setValue(newValue)} fieldName={fieldName} />
        </InputBlock>
    );
}
