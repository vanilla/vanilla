import { roleLookUp, userLookup } from "@dashboard/moderation/communityManagmentUtils";
import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { ReasonFilter } from "@dashboard/moderation/components/ReasonFilter";
import { ReportStatus, reportStatusLabel } from "@dashboard/moderation/components/ReportFilters.constants";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@vanilla/i18n";

export type IReportFilters = {
    /** Status of the report */
    statuses: string[];
    /** The reasons for the report */
    reportReasonID: string[];
    /** The person who reported */
    insertUserID: string[];
    /** Roles of the person who reported */
    insertUserRoleID: string[];
    /** The peron who made the post */
    recordUserID: string[];
};

interface IProps {
    value: IReportFilters;
    onFilter: (value: IReportFilters) => void;
}

export function ReportFilters(props: IProps) {
    const reportFilterOptions: ISelectBoxItem[] = [
        {
            name: reportStatusLabel(ReportStatus.NEW),
            value: ReportStatus.NEW,
        },
        {
            name: reportStatusLabel(ReportStatus.DISMISSED),
            value: ReportStatus.DISMISSED,
        },
        {
            name: reportStatusLabel(ReportStatus.REJECTED),
            value: ReportStatus.REJECTED,
        },
        {
            name: reportStatusLabel(ReportStatus.ESCALATED),
            value: ReportStatus.ESCALATED,
        },
    ];
    return (
        <FilterFrame title={t("Filter")} hideFooter>
            <FilterBlock
                apiName={"statuses"}
                label={"Status"}
                initialFilters={props.value.statuses}
                staticOptions={reportFilterOptions}
                onFilterChange={props.onFilter}
            />
            <ReasonFilter
                apiName={"reportReasonID"}
                label={"Reason"}
                initialFilters={props.value.reportReasonID}
                onFilterChange={props.onFilter}
            />
            <FilterBlock
                apiName={"insertUserID"}
                label={"Reporter"}
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
                apiName={"recordUserID"}
                label={"Post Author"}
                initialFilters={props.value.recordUserID}
                dynamicOptionApi={userLookup}
                onFilterChange={props.onFilter}
            />
        </FilterFrame>
    );
}
