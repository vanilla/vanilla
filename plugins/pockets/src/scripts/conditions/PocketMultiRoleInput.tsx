/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";

export function PocketMultiRoleInput(props: { tag?: keyof JSX.IntrinsicElements }) {
    const form = { viewRoleIDs: [] };

    console.log("PocketMultiRoleInput");

    const updateForm = viewRoleIDs => {
        console.log("viewRoleIDs: ", viewRoleIDs);
        return;
    };

    return (
        <DashboardFormGroup label={t("Roles")} tag={props.tag}>
            <div className="input-wrap">
                <MultiRoleInput
                    label={""}
                    value={form.viewRoleIDs ?? []}
                    onChange={viewRoleIDs => {
                        updateForm({ viewRoleIDs });
                    }}
                />
            </div>
        </DashboardFormGroup>
    );
}
