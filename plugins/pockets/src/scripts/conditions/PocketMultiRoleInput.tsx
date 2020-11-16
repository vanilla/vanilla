/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

export function PocketMultiRoleInput(props) {
    const [roles, setRoles] = useState(props.initialValue && props.initialValue !== "" ? props.initialValue : []);
    return (
        <DashboardFormGroup label={t("Roles")} tag={"div"}>
            <div className="input-wrap">
                <MultiRoleInput
                    showIndicator={true}
                    className={dashboardClasses().tokenInput}
                    label={""}
                    value={roles ?? []}
                    onChange={(viewRoleIDs) => {
                        setRoles(viewRoleIDs ?? []);
                    }}
                    menuPlacement={"auto"}
                />
            </div>
            {!roles || (roles.length === 0 && <input name={props.fieldName + []} type={"hidden"} value={[]} />)}
            {roles &&
                roles.map((role, key) => {
                    return <input key={key} name={props.fieldName + "[]"} type={"hidden"} value={role} />;
                })}
        </DashboardFormGroup>
    );
}
