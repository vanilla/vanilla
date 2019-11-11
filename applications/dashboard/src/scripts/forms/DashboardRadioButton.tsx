/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useDashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import RadioButton from "@library/forms/RadioButton";

interface IProps {
    disabled?: boolean;
    className?: string;
    label: string;
    value: string;
    name: string;
    note: string;
}

export function DashboardRadioButton(props: IProps) {
    const { onChange, value, isInline } = useDashboardRadioGroup();

    const controlledProps =
        onChange !== undefined && value !== undefined
            ? {
                  onChange: () => onChange(props.value),
                  checked: value === props.value,
              }
            : {};

    return <RadioButton {...props} {...controlledProps} isHorizontal={isInline} />;
}
