/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ICustomControl } from "@vanilla/json-schema-forms/src/types";

interface IProps {
    control: ICustomControl;
}

export function DashboardCustomComponent(props: IProps) {
    const { control } = props;
    const CustomComponent = control.component;

    return (
        <div className="input-wrap">
            <CustomComponent {...control.componentProps}>{control.componentProps.children}</CustomComponent>
        </div>
    );
}
