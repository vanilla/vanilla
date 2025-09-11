import { roleLookUp, userLookup } from "@dashboard/moderation/communityManagmentUtils";

import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { ReasonFilter } from "@dashboard/moderation/components/ReasonFilter";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { getEscalationStatuses } from "@dashboard/moderation/components/escalationStatuses";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import { useCurrentUser } from "@library/features/users/userHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

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
    const permissions = usePermissionsContext();
    const hasMemberRestrictions = getMeta("moderation.restrictMemberFilterUI", false);
    const communityModerate = permissions.hasPermission("community.moderate");
    const siteManage = permissions.hasPermission("site.manage");
    const currentUser = useCurrentUser();

    // AIDEV-NOTE: Show member filters unless restricted by config AND user lacks required permissions
    const shouldShowMemberFilters = !hasMemberRestrictions || communityModerate || siteManage;

    const escalationFilterOptions: ISelectBoxItem[] = Object.entries(getEscalationStatuses()).map(([id, label]) => {
        return { value: id, name: label };
    });

    // Create restricted options for assignedUserID when member filters are restricted
    const restrictedAssigneeOptions = [
        { value: "-4", name: "Unassigned" },
        { value: currentUser.userID.toString(), name: "Assigned to Me" },
    ];

    return (
        <>
            <h3>{t("Filters")}</h3>
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
            {!shouldShowMemberFilters && (
                <FilterBlock
                    apiName={"assignedUserID"}
                    label={"Assignee"}
                    initialFilters={props.value.assignedUserID}
                    staticOptions={restrictedAssigneeOptions}
                    onFilterChange={props.onFilter}
                />
            )}
            {shouldShowMemberFilters && (
                <>
                    <FilterBlock
                        apiName={"assignedUserID"}
                        label={"Assignee"}
                        initialFilters={props.value.assignedUserID}
                        dynamicOptionApi={{
                            ...userLookup,
                            searchUrl: "/escalations/lookup-assignee?name=%s*&limit=10",
                            optionOverride: [
                                { value: "-4", name: "Unassigned", data: { icon: deletedUserFragment() } },
                            ],
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
                </>
            )}
        </>
    );
}
