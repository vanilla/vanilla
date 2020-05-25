/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";

const sanitizeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value;
    } else {
        return !value || value === "" ? [] : JSON.parse(value);
    }
};

export function PocketMultiRoleInput(props) {
    const [roles, setRoles] = useState(sanitizeValue(props.defaultValue));

    console.log("props: ", props);

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
            {!roles || (roles.length === 0 && <input name={props.fieldName} type={"hidden"} value={[]} />)}
            {roles.map((role, key) => {
                return <input key={key} name={props.fieldName + "[]"} type={"hidden"} value={role} />;
            })}
        </DashboardFormGroup>
    );
}
