/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IControlProps, ICustomControl } from "@vanilla/json-schema-forms/src/types";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";

export function DashboardCustomComponent(props: IControlProps<ICustomControl>) {
    const { control, instance, onChange, errors } = props;
    const CustomComponent = control.component;

    return (
        <DashboardInputWrap>
            <CustomComponent
                {...(control.componentProps ?? {})}
                errors={errors}
                value={instance ?? props.schema.default}
                onChange={onChange}
            >
                {control.componentProps?.children}
            </CustomComponent>
        </DashboardInputWrap>
    );
}
