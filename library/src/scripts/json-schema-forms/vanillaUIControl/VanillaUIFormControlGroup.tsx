/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { FormGroup, FormGroupLabel } from "@vanilla/ui";
import { IControlGroupProps } from "../types";

interface IProps extends IControlGroupProps {
    children?: React.ReactNode;
}

export function VanillaUIFormControlGroup(props: IProps) {
    const { label, description, fullSize } = props.controls[0];
    if (fullSize) {
        return <>{props.children}</>;
    }
    return (
        <FormGroup>
            <FormGroupLabel description={description}>{label}</FormGroupLabel>
            {props.children}
        </FormGroup>
    );
}
