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
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";

interface IProps extends React.ComponentProps<typeof AutoComplete> {
    errors?: IFieldError[];
    afterInput?: React.ReactNode;
}

export function DashboardAutoComplete(props: IProps) {
    const { errors, afterInput, ...autoCompleteProps } = props;
    const { inputID } = useFormGroup();

    return (
        <DashboardInputWrap>
            <AutoComplete {...autoCompleteProps} id={inputID} size={"small"} />
            {props.errors && <ErrorMessages errors={props.errors} />}
            {props.afterInput}
        </DashboardInputWrap>
    );
}
