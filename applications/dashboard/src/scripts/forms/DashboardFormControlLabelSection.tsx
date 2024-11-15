/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { ISectionProps } from "@vanilla/json-schema-forms";
import React from "react";

export function DashboardFormControlLabelSection(props: React.PropsWithChildren<ISectionProps>) {
    if (props.title || props.description) {
        return (
            <DashboardFormGroup label={props.title} description={props.description}>
                <div>{props.children}</div>
            </DashboardFormGroup>
        );
    } else {
        return <>{props.children}</>;
    }
}
