/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { IEscalation, IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { CompactReportList } from "@dashboard/moderation/components/CompactReportList";
import { EscalationActionPanel } from "@dashboard/moderation/components/EscalationActionPanel";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { EscalationStatus } from "@dashboard/moderation/components/escalationStatuses";
import { PostDetail } from "@dashboard/moderation/components/PostDetail";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { css, cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { MetaItem, Metas } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import BackLink from "@library/routing/links/BackLink";
import { QueryClient, QueryClientProvider, useQuery } from "@tanstack/react-query";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ escalationID: string }> {}

const classes = {
    section: css({
        marginBottom: 24,
    }),
    title: css({
        display: "flex",
        marginTop: 8,
    }),
    backlink: css({
        position: "absolute",
        left: 0,
        top: 10,
        fontSize: 24,
        transform: "translateX(calc(-100% - 4px))",
        margin: 0,
    }),
    titleOverride: css({
        "& > div": {
            marginBottom: 28,
            "& h2": {
                marginBottom: 16,
            },
        },
    }),
    attachmentHeader: css({
        marginBottom: 12,
    }),
};

const override = css({
    // So specific 😓
    "& > div > h2 > span": {
        overflow: "visible",
    },
});

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

const discussion = LayoutEditorPreviewData.discussion();
const comments = LayoutEditorPreviewData.comments(5);

function EscalationsDetailPage(props: IProps) {
    const cmdClasses = communityManagementPageClasses();

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    const escalation = useQuery<any, IError, IEscalation>({
        queryFn: async () => {
            const response = await apiv2.get(`/escalations/${props.match.params.escalationID}?expand=users`);
            return response.data;
        },
        queryKey: ["escalation", props.match.params.escalationID],
        keepPreviousData: true,
    });

    const post = useQuery<any, IError, IDiscussion>({
        queryFn: async ({ queryKey }) => {
            const id = queryKey[1];
            if (!id) return Promise.resolve({ data: null });
            const response = await apiv2.get(`/discussions/${id}?expand=users&expand=category`);
            return response.data;
        },
        queryKey: ["post", escalation.data?.recordID],
    });

    // Until reports expands are available // Cannot use multiple expands
    const reports = useQuery<any, IError, IReport[]>({
        queryFn: async ({ queryKey }) => {
            const recordID = queryKey[1];
            const recordType = queryKey[2];
            if (!recordID) return Promise.resolve({ data: null });
            //  This API has a problem
            const response = await apiv2.get(`/reports?recordID=${recordID}&recordType=${recordType}&expand=users`);
            // return response.data;
            return CommunityManagementFixture.getEscalation().reports;
        },
        queryKey: ["reports", escalation.data?.recordID, escalation.data?.recordType],
    });

    return (
        <>
            <AdminLayout
                titleAndActionsContainerClassName={override}
                title={
                    <PageHeadingBox
                        title={
                            <>
                                <span className={classes.title}>
                                    <BackLink className={classes.backlink} />
                                    {/* TODO: Add skeleton here */}
                                    <span>{escalation.isSuccess ? escalation.data.name : t("Loading...")}</span>
                                </span>
                            </>
                        }
                        description={
                            <Metas>
                                {escalation.isSuccess ? (
                                    <>
                                        <MetaItem>
                                            <EscalationStatus status={escalation.status} />
                                        </MetaItem>
                                        <EscalationMetas escalation={escalation.data} />
                                    </>
                                ) : (
                                    // TODO: Add skeleton here
                                    t("Loading...")
                                )}
                            </Metas>
                        }
                    />
                }
                leftPanel={!isCompact && <ModerationNav />}
                rightPanel={<>{escalation.isSuccess && <EscalationActionPanel escalation={escalation.data} />}</>}
                content={
                    <section className={cx(cmdClasses.content, classes.titleOverride)}>
                        {post.isSuccess && (
                            <div>
                                <PostDetail discussion={post.data} truncatePost />
                            </div>
                        )}
                        {escalation.isSuccess && reports.isSuccess && (
                            <div>
                                <DashboardFormSubheading>{t("Reports")}</DashboardFormSubheading>
                                {escalation.data.countReports > 0 && <CompactReportList reports={reports.data} />}
                                {escalation.data.countReports === 0 && (
                                    <div>
                                        <p>{t("There are no reports for this post")}</p>
                                    </div>
                                )}
                            </div>
                        )}
                        {escalation.isSuccess && (escalation.data.attachments ?? []).length > 0 && (
                            <div>
                                <DashboardFormSubheading className={classes.attachmentHeader}>
                                    {t("Attachments")}
                                </DashboardFormSubheading>

                                {escalation.data.attachments?.map((attachment) => (
                                    <ReadableIntegrationContextProvider
                                        key={attachment.attachmentID}
                                        attachmentType={attachment.attachmentType}
                                    >
                                        <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                                    </ReadableIntegrationContextProvider>
                                ))}
                            </div>
                        )}

                        {escalation.isSuccess && (
                            <div>
                                <DashboardFormSubheading>{t("Comments")}</DashboardFormSubheading>
                                {escalation.data.countComments > 0 && (
                                    <QueryClientProvider client={queryClient}>
                                        {/* TODO: all things discussion here should become generic for an escalation*/}
                                        <DiscussionCommentsAsset
                                            renderTitle={false}
                                            comments={{
                                                data: comments,
                                                paging: LayoutEditorPreviewData.paging(5),
                                            }}
                                            apiParams={{
                                                discussionID: discussion.discussionID,
                                                limit: 30,
                                                page: 1,
                                            }}
                                            discussion={discussion}
                                        />
                                    </QueryClientProvider>
                                )}
                                {escalation.data.countComments === 0 && (
                                    <div>
                                        <p>{t("There are no comments for this escalation")}</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </section>
                }
            />
        </>
    );
}

export default EscalationsDetailPage;
