/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IControlProps, ICustomControl } from "@vanilla/json-schema-forms/src/types";

export function DashboardCustomComponent(props: IControlProps<ICustomControl>) {
    const { control, instance, onChange, ...rest } = props;
    const CustomComponent = control.component;

    return (
        <div className="input-wrap">
            <CustomComponent
                {...rest}
                {...(control.componentProps ?? {})}
                value={instance ?? props.schema.default}
                onChange={onChange}
            >
                {control.componentProps?.children}
            </CustomComponent>
        </div>
    );
}
