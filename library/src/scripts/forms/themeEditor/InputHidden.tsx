/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

export interface IInputHidden extends Omit<React.InputHTMLAttributes<HTMLInputElement>, "type" | "value"> {
    variableID: string;
    value: string;
}

export function InputHidden(props: IInputHidden) {
    return <input type="hidden" name={props.variableID} value={props.value} />;
}
