/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EscalationStatus } from "@dashboard/moderation/CommunityManagementTypes";
import { roleLookUp, userLookup } from "@dashboard/moderation/communityManagmentUtils";
import { getEscalationStatuses } from "@dashboard/moderation/components/escalationStatuses";
import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { ReasonFilter } from "@dashboard/moderation/components/ReasonFilter";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@vanilla/i18n";

export type IEscalationFilters = {
    /** Status of the escalation */
    statuses: string[];
    /** The reasons for the escalation */
    reportReasonID: string[];
    /** The person who is assigned to the escalation */
    assignedUserID: string[];
    /** The author of the post */
    recordUserID: string[];
    /** The role of the author of the post */
    recordUserRoleID: string[];
};

interface IProps {
    value: IEscalationFilters;
    onFilter: (value: IEscalationFilters) => void;
}

export function EscalationFilters(props: IProps) {
    const escalationFilterOptions: ISelectBoxItem[] = Object.entries(getEscalationStatuses()).map(([id, label]) => {
        return { value: id, name: label };
    });

    return (
        <FilterFrame title={t("Filter")} hideFooter>
            <FilterBlock
                apiName={"statuses"}
                label={"Status"}
                initialFilters={props.value.statuses}
                staticOptions={escalationFilterOptions}
                onFilterChange={props.onFilter}
            />
            <ReasonFilter
                apiName={"reportReasonID"}
                label={"Reason"}
                initialFilters={props.value.reportReasonID}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"assignedUserID"}
                label={"Assignee"}
                initialFilters={props.value.assignedUserID}
                dynamicOptionApi={{
                    ...userLookup,
                    optionOverride: [{ value: "-4", name: "Unassigned", data: { icon: deletedUserFragment() } }],
                }}
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
                label={"Post Author Role"}
                initialFilters={props.value.recordUserRoleID}
                dynamicOptionApi={roleLookUp}
                onFilterChange={props.onFilter}
            />
        </FilterFrame>
    );
}
