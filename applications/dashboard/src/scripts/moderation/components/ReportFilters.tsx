import type { IComment } from "@dashboard/@types/api/comment";
import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { roleLookUp, userLookup } from "@dashboard/moderation/communityManagmentUtils";
import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { ReasonFilter } from "@dashboard/moderation/components/ReasonFilter";
import { ReportStatus, reportStatusLabel } from "@dashboard/moderation/components/ReportFilters.constants";
import { Icon } from "@vanilla/icons";
import apiv2 from "@library/apiv2";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { PageBox } from "@library/layout/PageBox";
import { ListItem } from "@library/lists/ListItem";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import { MetaItem } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import InputBlock from "@library/forms/InputBlock";
import { css } from "@emotion/css";

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
    recordType?: string;
    recordID?: string;
};

interface IProps {
    value: IReportFilters;
    onFilter: (value: IReportFilters) => void;
}

export function ReportFilters(props: IProps) {
    const { value, onFilter } = props;
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

    const hasRecordFilter = !!value.recordType && !!value.recordID;
    const reportRecordQuery = useQuery({
        queryFn: async (): Promise<IDiscussion | IComment | null> => {
            if (!value.recordType || !value.recordID) {
                return null;
            }

            const params = {
                expand: ["category"],
            };

            switch (value.recordType) {
                case "discussion":
                    return (await apiv2.get(`/discussions/${value.recordID}`, { params })).data;
                case "comment":
                    return (await apiv2.get(`/comments/${value.recordID}`, { params })).data;
            }
            return null;
        },
        enabled: hasRecordFilter,
        queryKey: ["reportRecord", value.recordType, value.recordID],
    });

    return (
        <>
            <h3>{t("Filter")}</h3>
            {hasRecordFilter && (
                <>
                    <InputBlock label={value.recordType === "comment" ? t("Comment") : t("Discussion")}>
                        <ListItem
                            className={classes.recordItem}
                            asTile={false}
                            as={"div"}
                            headingDepth={5}
                            boxOptions={{ borderType: BorderType.NONE }}
                            name={reportRecordQuery.data?.name}
                            actionAlignment={"center"}
                            metas={
                                <>
                                    <MetaItem>
                                        <Translate
                                            source="Posted by <0/> in <1/>"
                                            c0={
                                                reportRecordQuery.data?.insertUser ? (
                                                    <ProfileLink
                                                        asMeta
                                                        userFragment={reportRecordQuery.data?.insertUser}
                                                    />
                                                ) : (
                                                    <LoadingRectangle height={12} width={40} />
                                                )
                                            }
                                            c1={
                                                reportRecordQuery.data?.category ? (
                                                    <SmartLink to={reportRecordQuery.data.category.url} asMeta>
                                                        {reportRecordQuery.data.category.name}
                                                    </SmartLink>
                                                ) : (
                                                    <LoadingRectangle height={12} width={40} />
                                                )
                                            }
                                        />
                                    </MetaItem>
                                </>
                            }
                            actions={
                                <Button
                                    buttonType={ButtonTypes.ICON}
                                    onClick={() => {
                                        onFilter({
                                            ...value,
                                            recordType: undefined,
                                            recordID: undefined,
                                        });
                                    }}
                                >
                                    <Icon icon="filter-remove" />
                                </Button>
                            }
                        />
                    </InputBlock>
                </>
            )}
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
        </>
    );
}

const classes = {
    recordItem: css({
        marginTop: 8,
    }),
};
