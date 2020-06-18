/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { IRadioGroupProps, withRadioGroup } from "@library/forms/radioAsButtons/RadioGroupContext";
import RadioAsButton from "@library/forms/radioTabs/RadioAsButton";

export interface IRadioInputAsButton {
    label: string;
    icon?: JSX.Element;
    className?: string;
    data: string | number;
    disabled?: boolean;
    isLoading?: boolean;
    labelClass?: string;
}

/**
 * Implement what looks like buttons, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
export function RadioInputAsButton(props: IRadioInputAsButton) {
    const { data } = props;
    const activeItem = props["activeItem"];
    return <RadioAsButton {...props} active={activeItem !== undefined ? activeItem === data : false} />;
}

interface IRadioInputAsButtonInGroup extends IRadioInputAsButton, IRadioGroupProps {}
export default withRadioGroup<IRadioInputAsButtonInGroup>(RadioInputAsButton);
