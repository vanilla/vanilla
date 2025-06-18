/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { categoryLookup, roleLookUp, userLookup } from "@dashboard/moderation/communityManagmentUtils";
import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import {
    TriageInternalStatus,
    TriageInternalStatusLabels,
} from "@dashboard/moderation/components/TriageFilters.constants";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n";

export type ITriageFilters = {
    /** Resolution status */
    internalStatusID: TriageInternalStatus[];
    /** The person who made the post */
    insertUserID: string[];
    /** The role of the person who made the post */
    insertUserRoleID: string[];
    /** The category of the post */
    categoryID: string[];
};

const triageFilterOptions: ISelectBoxItem[] = [
    {
        name: TriageInternalStatusLabels[TriageInternalStatus.RESOLVED],
        value: TriageInternalStatus.RESOLVED,
    },
    {
        name: TriageInternalStatusLabels[TriageInternalStatus.UNRESOLVED],
        value: TriageInternalStatus.UNRESOLVED,
    },
];

interface IProps {
    value: ITriageFilters;
    onFilter: (value: ITriageFilters) => void;
}

export function TriageFilters(props: IProps) {
    return (
        <>
            <h3>{t("Filter")}</h3>

            <FilterBlock
                apiName={"internalStatusID"}
                label={"Status"}
                initialFilters={props.value.internalStatusID}
                staticOptions={triageFilterOptions}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"insertUserID"}
                label={"Post Author"}
                initialFilters={props.value.insertUserID}
                dynamicOptionApi={userLookup}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"insertUserRoleID"}
                label={"Reporter Role"}
                initialFilters={props.value.insertUserRoleID}
                dynamicOptionApi={roleLookUp}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"categoryID"}
                label={"Category"}
                initialFilters={props.value.categoryID}
                dynamicOptionApi={categoryLookup}
                onFilterChange={props.onFilter}
            />
        </>
    );
}
