/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import InputBlock, { IInputBlockProps } from "@library/forms/InputBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

interface IProps extends IInputBlockProps {}

export default function CheckboxGroup(props: IProps) {
    const classes = inputBlockClasses();
    return (
        <InputBlock {...props} legend={true}>
            {props.children}
        </InputBlock>
    );
}
