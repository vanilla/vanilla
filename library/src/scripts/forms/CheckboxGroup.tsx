/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import InputBlock, { IInputBlockProps } from "@library/forms/InputBlock";
import classNames from "classnames";

export interface ICheckboxGroup extends IInputBlockProps {}

export default class CheckboxGroup extends React.Component<ICheckboxGroup> {
    public render() {
        return (
            <InputBlock {...this.props} legend={true}>
                {this.props.children}
            </InputBlock>
        );
    }
}
