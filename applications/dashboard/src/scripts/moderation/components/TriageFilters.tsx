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
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@vanilla/i18n";

export type ITriageFilters = {
    /** Resolution status */
    recordInternalStatusID: TriageInternalStatus[];
    /** The person who made the post */
    recordUserID: string[];
    /** The role of the person who made the post */
    recordUserRoleID: string[];
    /** The category of the post */
    placeRecordID: string[];
    /** Needed if filtered by category */
    placeRecordType?: string;
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
        <FilterFrame title={t("Filter")} hideFooter>
            <FilterBlock
                apiName={"recordInternalStatusID"}
                label={"Status"}
                initialFilters={props.value.recordInternalStatusID}
                staticOptions={triageFilterOptions}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"recordUserID"}
                label={"Post Author"}
                initialFilters={props.value.recordUserID}
                dynamicOptionApi={userLookup}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"recordUserRoleID"}
                label={"Reporter Role"}
                initialFilters={props.value.recordUserRoleID}
                dynamicOptionApi={roleLookUp}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"placeRecordID"}
                label={"Category"}
                initialFilters={props.value.placeRecordID}
                dynamicOptionApi={categoryLookup}
                onFilterChange={props.onFilter}
            />
        </FilterFrame>
    );
}
