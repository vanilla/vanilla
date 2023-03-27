/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { AutoComplete } from "@vanilla/ui/src/forms/autoComplete";

interface IProps extends React.ComponentProps<typeof AutoComplete> {
    errors?: IFieldError[];
    afterInput?: React.ReactNode;
}

export function DashboardAutoComplete(props: IProps) {
    const { errors, afterInput, ...autoCompleteProps } = props;
    const { inputID, labelType } = useFormGroup();

    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <AutoComplete {...autoCompleteProps} id={inputID} />
            {props.errors && <ErrorMessages errors={props.errors} />}
            {props.afterInput}
        </div>
    );
}
