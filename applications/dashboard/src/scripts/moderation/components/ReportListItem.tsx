/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { isHtmlEmpty } from "@dashboard/moderation/communityManagmentUtils";
import { reportListItemClasses } from "@dashboard/moderation/components/ReportListItem.classes";
import { ReportRecordMeta } from "@dashboard/moderation/components/ReportRecordMeta";
import { cx } from "@emotion/css";
import { CollapsableContent } from "@library/content/CollapsableContent";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Heading from "@library/layout/Heading";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaIcon, MetaItem, Metas, MetaProfile } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { LocationDescriptor } from "history";
import { useState } from "react";
import BlurContainer from "@dashboard/moderation/components/BlurContainerUserContent";
import LinkAsButton from "@library/routing/LinkAsButton";
import { Icon } from "@vanilla/icons";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { EscalateModal } from "@dashboard/moderation/components/EscalateModal";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useToast } from "@library/features/toaster/ToastContext";
import { ReportStatus, reportStatusLabel } from "@dashboard/moderation/components/ReportFilters.constants";

interface IProps {
    report: IReport;
}

export function ReportListItem(props: IProps) {
    const { report } = props;
    const classes = reportListItemClasses();
    const url = "/dashboard/content/triage/" + report.reportID;
    const [showEscalate, setShowEscalate] = useState(false);

    const queryClient = useQueryClient();
    const toast = useToast();
    function invalidateCaches() {
        queryClient.invalidateQueries(["reports"]);
        queryClient.invalidateQueries(["post"]);
    }

    const dismissMutation = useMutation({
        mutationFn: async () => {
            const response = await apiv2.patch(`/reports/${report.reportID}/dismiss`);
            invalidateCaches();
            toast.addToast({
                autoDismiss: true,
                body: t("Report dismissed."),
            });
            return response;
        },
        mutationKey: [report.reportID],
    });

    const approveMutation = useMutation({
        mutationFn: async () => {
            const response = await apiv2.patch(`/reports/${report.reportID}/approve-record`);
            invalidateCaches();
            toast.addToast({
                autoDismiss: true,
                body: t("Post approved."),
            });
            return response;
        },
        mutationKey: [report.reportID],
    });

    const rejectMutation = useMutation({
        mutationFn: async () => {
            const response = await apiv2.patch(`/reports/${report.reportID}/reject-record`);
            invalidateCaches();
            toast.addToast({
                autoDismiss: true,
                body: t("Post rejected."),
            });
            return response;
        },
        mutationKey: [report.reportID],
    });

    return (
        <div className={classes.container}>
            <header className={classes.header}>
                <Metas>
                    {report.reasons.map((reason) => (
                        <MetaItem key={reason.reportReasonID}>
                            <Tag preset={TagPreset.STANDARD} tooltipLabel={reason.description}>
                                {reason.name}
                            </Tag>
                        </MetaItem>
                    ))}

                    <MetaItem flex>
                        <Translate
                            source={"Reported by <0/>"}
                            c0={<MetaProfile user={report.insertUser ?? deletedUserFragment()} />}
                        />
                    </MetaItem>
                    <MetaItem>
                        <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                        <DateTime timestamp={report.dateInserted}></DateTime>
                    </MetaItem>
                </Metas>
                <span style={{ flex: 1 }}></span>
                <Notice className={classes.status}>{reportStatusLabel(report.status)}</Notice>
            </header>
            {/* Report */}
            <ListItem
                as={"div"}
                className={cx(classes.reportItem, classes.adminStyleOverrides)}
                descriptionClassName={classes.recordOverrides}
                description={
                    isHtmlEmpty(report.noteHtml) ? (
                        <Translate source={"No report notes were provided by <0/>"} c0={report.insertUser?.name} />
                    ) : (
                        <UserContent content={report.noteHtml} />
                    )
                }
                truncateDescription={false}
            />

            {/* Record */}
            <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                <BlurContainer>
                    <ListItem
                        className={cx(classes.recordItem, classes.adminStyleOverrides)}
                        nameClassName={communityManagementPageClasses().listItemLink}
                        as={"div"}
                        name={
                            <span className={detailPageClasses().headerIconLayout}>
                                {report.recordName}
                                <span>
                                    <ToolTip label={t("View post in community")}>
                                        <span>
                                            <Icon icon="meta-external" size={"compact"} />
                                        </span>
                                    </ToolTip>
                                </span>
                            </span>
                        }
                        url={report.recordUrl}
                        description={
                            <CollapsableContent maxHeight={50} gradientClasses={classes.gradientOverride}>
                                <UserContent content={report.recordHtml} moderateEmbeds />{" "}
                            </CollapsableContent>
                        }
                        truncateDescription={false}
                        metas={<ReportRecordMeta record={report} />}
                    />
                </BlurContainer>
            </ListItemContext.Provider>
            <div className={classes.reportSummaryContainer}>
                <div className={classes.reportSummary}>
                    <>
                        {report.status === "new" ? (
                            <>
                                {report.isPending ? (
                                    <>
                                        <Button
                                            buttonType={ButtonTypes.TEXT}
                                            onClick={() => {
                                                rejectMutation.mutate();
                                            }}
                                        >
                                            {rejectMutation.isLoading ? <ButtonLoader /> : t("Reject")}
                                        </Button>
                                        <Button
                                            buttonType={ButtonTypes.TEXT}
                                            onClick={() => {
                                                approveMutation.mutate();
                                            }}
                                        >
                                            {approveMutation.isLoading ? <ButtonLoader /> : t("Approve")}
                                        </Button>
                                    </>
                                ) : (
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => {
                                            dismissMutation.mutate();
                                        }}
                                    >
                                        {dismissMutation.isLoading ? <ButtonLoader /> : t("Dismiss")}
                                    </Button>
                                )}
                            </>
                        ) : (
                            <></>
                        )}

                        {report.escalationUrl ? (
                            <SmartLink to={report.escalationUrl}>{t("View Escalation")}</SmartLink>
                        ) : (
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                onClick={() => {
                                    setShowEscalate(true);
                                }}
                            >
                                {t("Escalate")}
                            </Button>
                        )}
                    </>
                </div>
            </div>
            <EscalateModal
                escalationType={"report"}
                report={report}
                isVisible={showEscalate}
                onClose={() => {
                    setShowEscalate(false);
                }}
            />
        </div>
    );
}
