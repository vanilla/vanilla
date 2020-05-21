/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import { componentExists, getComponent } from "@library/utility/componentRegistry";

const sanitizeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value;
    } else {
        return !value || value === "" ? [] : JSON.parse(value);
    }
};

export function PocketSubcommunityChooser(props) {
    const componentName = "multi-subcommunity-input";
    const initialValues = sanitizeValue(props.value);
    const [subcommunities, setSubcommunities] = useState(sanitizeValue(initialValues));

    // Must be after the useEffect and useState
    let MultiSubcommunityInput;
    if (componentExists(componentName)) {
        MultiSubcommunityInput = getComponent(componentName);
    } else {
        return null;
    }

    return (
        <DashboardFormGroup label={t("Subcommunities")} tag={"div"}>
            <div className="input-wrap">
                <MultiSubcommunityInput.Component
                    value={subcommunities ?? []}
                    onChange={selectedSubCommunities => {
                        setSubcommunities(
                            selectedSubCommunities.map(subCom => {
                                return parseInt(subCom.value);
                            }),
                        );
                    }}
                />
            </div>
            <input name={props.fieldName} type={"hidden"} value={JSON.stringify(subcommunities)} />
        </DashboardFormGroup>
    );
}
