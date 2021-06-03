/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { languageSettingsStyles } from "@dashboard/languages/LanguageSettings.styles";
import { IControlProps } from "@vanilla/json-schema-forms";
import { TextBox } from "@vanilla/ui";
import React from "react";

export const LanguageSettingsFormControls = (props: IControlProps) => {
    const { disabled, onChange, control, instance, required } = props;

    const classes = languageSettingsStyles();

    switch (control.inputType) {
        case "textBox": {
            const { label, placeholder } = control;
            return (
                <div className={classes.textBox}>
                    <label htmlFor={label}>{label}</label>
                    <TextBox
                        required={required}
                        disabled={disabled}
                        placeholder={placeholder}
                        defaultValue={instance}
                        onChange={(event: React.ChangeEvent<HTMLInputElement>) => onChange(event.target.value)}
                    />
                </div>
            );
        }
    }
    return null;
};
