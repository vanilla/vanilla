/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";

export function PocketMultiRoleInput(props: { tag?: keyof JSX.IntrinsicElements; id?: string }) {
    const { id = "js-pocketRole" } = props;
    const [roles, setRoles] = useState([] as number[]);

    const formField = useMemo(() => {
        return document.getElementById(id);
    }, []) as HTMLInputElement;

    useEffect(() => {
        if (formField) {
            formField.value = JSON.stringify(roles);
        }
    }, [roles]);

    // initial loading of values
    useEffect(() => {
        console.log("formField.value: ", formField.value);
        setRoles(JSON.parse(formField.value));
    }, [formField]);

    return (
        <DashboardFormGroup label={t("Roles")} tag={props.tag}>
            <div className="input-wrap">
                <MultiRoleInput
                    label={""}
                    value={roles ?? []}
                    onChange={viewRoleIDs => {
                        setRoles(viewRoleIDs ?? []);
                    }}
                />
            </div>
        </DashboardFormGroup>
    );
}
