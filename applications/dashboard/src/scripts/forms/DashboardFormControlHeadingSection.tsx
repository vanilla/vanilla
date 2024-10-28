/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ISectionProps } from "@vanilla/json-schema-forms";
import React from "react";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";

export function DashboardFormControlHeadingSection(props: React.PropsWithChildren<ISectionProps>) {
    return (
        <>
            {props.title && <DashboardFormSubheading hasBackground>{props.title}</DashboardFormSubheading>}
            {props.children}
        </>
    );
}
