/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";

const sanitizeValue = (value: any) => {
    return !value || value === "" ? [] : JSON.parse(value);
};

export function PocketMultiRoleInput(props: { tag?: keyof JSX.IntrinsicElements; id?: string; initialValue?: string }) {
    const { id = "js-pocketRole" } = props;
    const initialValue = sanitizeValue(props.initialValue);
    const [roles, setRoles] = useState(initialValue);

    const formField = useMemo(() => {
        return document.getElementById(id);
    }, []) as HTMLInputElement;

    useEffect(() => {
        setRoles(sanitizeValue(formField.value));
    }, [roles]);

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
