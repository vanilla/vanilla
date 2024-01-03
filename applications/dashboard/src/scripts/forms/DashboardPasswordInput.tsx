/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IFieldError } from "@library/@types/api/core";
import PasswordInput from "@library/forms/PasswordInput";
import InputBlock from "@library/forms/InputBlock";
import { IInputTextProps } from "@library/forms/InputTextBlock";
import { t } from "@vanilla/i18n";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import Button from "@library/forms/Button";
import gdn from "@library/gdn";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps extends IInputTextProps {
    onChange: (newValue: string) => void;
    errors?: IFieldError[];
    disabled?: boolean;
    renderGeneratePasswordButton?: boolean;
}

export function DashboardPasswordInput(props: IProps) {
    const { inputProps, renderGeneratePasswordButton } = props;
    const { inputID } = useFormGroup();

    return (
        <div className={"input-wrap"}>
            <InputBlock errors={props.errors} noMargin extendErrorMessage>
                <PasswordInput
                    id={inputID}
                    showUnmask
                    onChange={(event) => {
                        props.onChange(event.target.value);
                    }}
                    value={inputProps?.value}
                    aria-label={t(inputProps?.["aria-label"] ?? "Current Password")}
                    required={inputProps?.required ?? false}
                    className={dashboardClasses().passwordInput}
                />
            </InputBlock>
            {!!renderGeneratePasswordButton && (
                <InputBlock>
                    <Button
                        buttonType={ButtonTypes.OUTLINE}
                        onClick={() => {
                            props.onChange(gdn.generateString(inputProps?.minLength ?? 12));
                        }}
                    >
                        {t("Generate Password")}
                    </Button>
                </InputBlock>
            )}
        </div>
    );
}
