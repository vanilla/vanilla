/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import InputBlock, { IInputBlockProps } from "@library/forms/InputBlock";

export interface IRadioButtonGroup extends IInputBlockProps {}

export default function RadioButtonGroup(props: Omit<IInputBlockProps, "label">) {
    return (
        <InputBlock {...props} legend={props.legend ?? ""}>
            {props.children}
        </InputBlock>
    );
}
