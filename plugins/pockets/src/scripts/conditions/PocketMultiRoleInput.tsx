/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";

export function PocketMultiRoleInput(props: { tag?: keyof JSX.IntrinsicElements; id?: string; initialValue?: string }) {
    const { id = "js-pocketRole" } = props;
    const [roles, setRoles] = useState(props.initialValue ? JSON.parse(props.initialValue) : "[]");
    const [disabled, setDisabled] = useState(true);

    const formField = useMemo(() => {
        return document.getElementById(id);
    }, []) as HTMLInputElement;

    useEffect(() => {
        setRoles(JSON.parse(formField.value ?? []));
    }, [roles]);

    return (
        <DashboardFormGroup label={t("Roles")} tag={props.tag}>
            <div className="input-wrap">
                <MultiRoleInput
                    label={""}
                    value={roles ?? []}
                    disabled={disabled}
                    loadedCallback={() => {
                        console.log("finito");
                    }}
                    onChange={viewRoleIDs => {
                        setRoles(viewRoleIDs ?? []);
                    }}
                />
            </div>
        </DashboardFormGroup>
    );
}
