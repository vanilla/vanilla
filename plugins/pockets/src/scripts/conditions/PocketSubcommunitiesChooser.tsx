/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { SubcommunityChooser } from "@subcommunities/chooser/SubcommunityChooser";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import Loader from "@library/loaders/Loader";

const sanitizeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value;
    } else {
        return !value || value === "" ? [] : JSON.parse(value);
    }
};

export function PocketSubcommunityChooser(props) {
    const [activeSection, setActiveSection] = useState(props.value);

    // const { subcommunitiesByID } = useSubcommunities();
    // const communityData = subcommunitiesByID.data;
    //
    // if (!communityData) {
    //     return <Loader small />;
    // }
    //
    // return (
    //     <ul>
    //         {props.subcommunityIDs.map(id => {
    //             return <li key={id}>{communityData[id].name + ` (${communityData[id].locale})`}</li>;
    //         })}
    //     </ul>
    // );

    return (
        <SubcommunityChooser activeSection={activeSection} setActiveSection={setActiveSection} />
        // <DashboardFormGroup label={t("Roles")} tag={props.tag}>
        //     <div className="input-wrap">
        //         <MultiRoleInput
        //             label={""}
        //             value={roles ?? []}
        //             onChange={viewRoleIDs => {
        //                 setRoles(viewRoleIDs ?? []);
        //             }}
        //         />
        //     </div>
        //     <input name={props.fieldName} type={"hidden"} value={JSON.stringify(roles)} />
        // </DashboardFormGroup>
    );
}
