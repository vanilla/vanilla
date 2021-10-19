/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { formTreeClasses } from "@library/tree/FormTree.classes";
import { makeFormTreeLabelID, useFormTreeContext, useFormTreeLabels } from "@library/tree/FormTreeContext";
import { IControlProps } from "@vanilla/json-schema-forms";
import { VanillaUIFormControl } from "@vanilla/json-schema-forms/src/vanillaUIControl/VanillaUIFormControl";
import { VanillaUIFormControlContext } from "@vanilla/json-schema-forms/src/vanillaUIControl/VanillaUIFormControlContext";
import React from "react";

export function FormTreeRowFormControl(props: IControlProps) {
    const classes = formTreeClasses();
    const treeContext = useFormTreeContext();

    const labels = useFormTreeLabels();
    const propertyName = props.path[props.path.length - 1];
    const hasLabel = propertyName in labels;
    const labelID =
        typeof propertyName === "string" && hasLabel ? makeFormTreeLabelID(treeContext, propertyName) : undefined;

    return (
        <div className={classes.inputWrapper}>
            <VanillaUIFormControlContext.Provider
                value={{
                    commonInputProps: {
                        "aria-labelledby": labelID,
                    },
                    inputTypeProps: {
                        textBox: {
                            className: classes.input,
                            size: "small",
                        },
                        dropDown: {
                            className: classes.autoComplete,
                            inputClassName: classes.input,
                            size: "small",
                        },
                    },
                }}
            >
                <VanillaUIFormControl {...props} />
            </VanillaUIFormControlContext.Provider>
        </div>
    );
}
