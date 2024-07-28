import { IDiscussion } from "@dashboard/@types/api/discussion";
import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { CompactReportList } from "@dashboard/moderation/components/CompactReportList";
import { DismissReportModal } from "@dashboard/moderation/components/DismissReportModal";
import { EscalateModal } from "@dashboard/moderation/components/EscalateModal";
import { PostDetail } from "@dashboard/moderation/components/PostDetail";
import { TriageActionPanel } from "@dashboard/moderation/components/TriageActionPanel";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { TriageListItem } from "@dashboard/moderation/components/TriageListItem";
import { css } from "@emotion/css";
import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import BackLink from "@library/routing/links/BackLink";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { report } from "process";
import { useState } from "react";
import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ recordID: string }> {}

const layout = css({
    margin: "16px 28px",
    "& > div": {
        marginBottom: 28,
        "& h2": {
            marginBottom: 16,
        },
    },
});

const backlink = css({
    position: "absolute",
    left: 4,
    top: "50%",
    fontSize: 24,
    transform: "translateY(-50%)",
});

function TriageDetailPage(props: IProps) {
    const recordID = props.match.params.recordID;

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    const [reportToEscalate, setReportToEscalate] = useState<IReport | null>(null);

    const post = useQuery<any, IError, IDiscussion>({
        queryFn: async () => {
            const response = await apiv2.get(
                `/discussions/${recordID}?expand=users&expand=category&expand=attachments`,
            );
            return response.data;
        },
        queryKey: ["post", recordID],
    });

    const triageItem = useQuery<any, IError, IReport[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/reports?recordID=${recordID}&recordType=discussion&expand=users`);
            return response.data;
        },
        queryKey: ["triageItem", recordID],
    });

    const queryClient = useQueryClient();
    const resolveMutation = useMutation({
        mutationFn: (options: { discussionID: string; internalStatusID: TriageInternalStatus }) => {
            const { discussionID, internalStatusID } = options;
            return apiv2.put(`/discussions/${discussionID}/status`, {
                internalStatusID: internalStatusID,
            });
        },
        onSuccess() {
            queryClient.invalidateQueries(["post"]);
        },
    });

    const [showDismiss, setShowDismiss] = useState(false);

    return (
        <>
            <AdminLayout
                title={
                    <>
                        <BackLink className={backlink} />
                        {post.isSuccess ? (
                            <Translate source="Reports for <0/>" c0={post?.data?.name ?? ""} />
                        ) : (
                            t("Loading...")
                        )}
                    </>
                }
                leftPanel={!isCompact && <ModerationNav />}
                rightPanel={
                    <TriageActionPanel
                        hasReports={!!(triageItem?.data && triageItem?.data.length !== 0)}
                        isResolved={!!post?.data?.resolved}
                        onEscalate={() => setReportToEscalate(triageItem?.data?.[0] ?? null)}
                        onDismiss={() => setShowDismiss(true)}
                        onResolve={() => {
                            resolveMutation.mutate({
                                discussionID: recordID,
                                internalStatusID: post?.data?.resolved
                                    ? TriageInternalStatus.UNRESOLVED
                                    : TriageInternalStatus.RESOLVED,
                            });
                        }}
                    />
                }
                content={
                    <section className={layout}>
                        {post.isSuccess && (
                            <div>
                                <PostDetail discussion={post.data} />
                            </div>
                        )}
                        {triageItem.isSuccess && (
                            <div>
                                <DashboardFormSubheading>{t("Reports")}</DashboardFormSubheading>
                                {triageItem.data.length > 0 && <CompactReportList reports={triageItem.data} />}
                                {triageItem.data.length === 0 && (
                                    <div>
                                        <p>{t("There are no reports for this post")}</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </section>
                }
            />
            <EscalateModal
                report={reportToEscalate}
                recordID={reportToEscalate?.recordID ?? null}
                recordType={reportToEscalate?.recordType ?? null}
                isVisible={!!reportToEscalate}
                onClose={() => {
                    setReportToEscalate(null);
                }}
            />
            <DismissReportModal
                reportIDs={triageItem?.data?.map((report) => report.reportID) ?? []}
                isVisible={showDismiss}
                onClose={() => {
                    setShowDismiss(false);
                }}
            />
        </>
    );
}

export default TriageDetailPage;
