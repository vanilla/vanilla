/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useDashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import CheckBox from "@library/forms/Checkbox";

interface IProps {
    disabled?: boolean;
    className?: string;
    label: string;
    checked?: boolean;
    name?: string;
    onChange?: (newValue: boolean) => void;
    excludeFromICheck?: boolean;
}

export function DashboardCheckBox(props: IProps) {
    const { isInline } = useDashboardRadioGroup();
    const { excludeFromICheck = true } = props;

    return (
        <CheckBox
            {...props}
            excludeFromICheck={excludeFromICheck}
            onChange={(e) => props.onChange && props.onChange(!!e.target.checked)}
            isHorizontal={isInline}
        />
    );
}
