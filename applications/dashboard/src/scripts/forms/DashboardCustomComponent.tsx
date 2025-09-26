/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IControlProps, ICustomControl } from "@vanilla/json-schema-forms/src/types";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { getComponent } from "@library/utility/componentRegistry";
import ErrorMessages from "@library/forms/ErrorMessages";

export function DashboardCustomComponent(props: IControlProps<ICustomControl>) {
    const { control, instance, onChange, errors } = props;
    let CustomComponent: any = control.component;

    if (typeof CustomComponent === "string") {
        const foundComponent = getComponent(CustomComponent);
        if (foundComponent) {
            CustomComponent = foundComponent.Component;
        } else {
            return (
                <ErrorMessages
                    errors={[
                        {
                            message: `Custom component ${CustomComponent} not found.`,
                        },
                    ]}
                />
            );
        }
    }

    return (
        <DashboardInputWrap>
            <CustomComponent
                {...props}
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
