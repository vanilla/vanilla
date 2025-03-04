/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { usePostRevisions, useReportsQuery } from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import type { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { PostRevisionPicker } from "@dashboard/moderation/PostRevisionPicker";
import { EscalateModal } from "@dashboard/moderation/components/EscalateModal";
import { IMessageInfo, MessageAuthorModal } from "@dashboard/moderation/components/MessageAuthorModal";
import { PostDetail } from "@dashboard/moderation/components/PostDetail";
import { ReportHistoryList } from "@dashboard/moderation/components/ReportHistoryList";
import { reportHistoryListClasses } from "@dashboard/moderation/components/ReportHistoryList.classes";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { isSameOrAfterDate } from "@library/content/DateTimeHelpers";
import Translate from "@library/content/Translate";
import { useDiscussionQuery } from "@library/features/discussions/discussionHooks";
import { PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { Row } from "@library/layout/Row";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Message from "@library/messages/Message";
import DocumentTitle from "@library/routing/DocumentTitle";
import LinkAsButton from "@library/routing/LinkAsButton";
import BackLink from "@library/routing/links/BackLink";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { Sort } from "@library/sort/Sort";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentItem } from "@vanilla/addon-vanilla/comments/CommentItem";
import { t } from "@vanilla/i18n";
import { labelize, notEmpty, type RecordID } from "@vanilla/utils";
import { useMemo, useState } from "react";
import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ recordID: string }> {}

interface ImplProps {
    reports: IReport[];
    discussion: IDiscussion;
}

function TriageDetailPageImpl(props: ImplProps) {
    const { discussion, reports } = props;
    const recordID = discussion.discussionID;

    const cmdClasses = communityManagementPageClasses();
    const classes = detailPageClasses();
    const postRevisions = usePostRevisions(reports, discussion);

    const [selectedTabIndex, setSelectedTabIndex] = useState(0);
    const [escalationModalVisibility, setEscalationModalVisibility] = useState(false);

    const queryClient = useQueryClient();
    const resolveMutation = useMutation({
        mutationFn: (options: { discussionID: RecordID; internalStatusID: TriageInternalStatus }) => {
            const { discussionID, internalStatusID } = options;
            return apiv2.put(`/discussions/${discussionID}/status`, {
                internalStatusID: internalStatusID,
            });
        },
        async onSuccess() {
            await queryClient.invalidateQueries(["discussion"]);

            // These one don't need to be resolved synchronously.
            void queryClient.invalidateQueries(["triageItems"]);
            void queryClient.invalidateQueries(["post", recordID]);
        },
    });

    const [authorMessage, setAuthorMessage] = useState<IMessageInfo | null>(null);

    const discussionComments = useQuery<any, IApiError, IComment[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/comments`, {
                params: {
                    discussionID: recordID,
                    expand: "all",
                },
            });
            return response.data;
        },
        queryKey: ["comments", recordID],
    });

    const escalation = discussion?.attachments?.find((item) => item.attachmentType === "vanilla-escalation");

    const filteredDiscussionComments = useMemo(() => {
        if (discussion && discussionComments.data) {
            const dateUpdated = discussion?.internalStatus?.log?.dateUpdated;
            if (dateUpdated) {
                let viewed: IComment[] = [];
                let newComments: IComment[] = [];
                discussionComments.data.forEach((comment: IComment) => {
                    if (isSameOrAfterDate(new Date(`${dateUpdated}`), new Date(`${comment.dateInserted}`))) {
                        viewed.push(comment);
                    } else {
                        newComments.push(comment);
                    }
                });
                return {
                    new: newComments,
                    viewed,
                    all: discussionComments.data ?? [],
                };
            }
        }

        return {
            all: discussionComments.data ?? [],
        };
    }, [discussionComments.data, discussion]);

    const makeTabData = (separatedComments: Record<string, IComment[] | undefined>): ITabData[] => {
        const hasNew = separatedComments.new?.length ?? 0 > 0;
        const hasViewed = separatedComments.viewed?.length ?? 0 > 0;
        if (hasNew && !hasViewed) {
            separatedComments = {
                new: separatedComments.new ?? [],
            };
        }

        if (hasViewed && !hasNew) {
            separatedComments = {
                viewed: separatedComments.viewed ?? [],
            };
        }

        return Object.entries(separatedComments)
            .map(([key, comments]) => {
                if (comments?.length !== 0) {
                    return {
                        tabID: key,
                        label: <Translate source={"<0/> Post Comments"} c0={labelize(key)} />,
                        contents: (
                            <>
                                {comments?.map((comment) => {
                                    return (
                                        <CommentItem
                                            key={comment.commentID}
                                            comment={comment}
                                            userPhotoLocation={"left"}
                                            boxOptions={{
                                                borderType: BorderType.SEPARATOR_BETWEEN,
                                            }}
                                        />
                                    );
                                })}
                            </>
                        ),
                    };
                }
            })
            .filter(notEmpty);
    };

    const { hasPermission } = usePermissionsContext();

    return (
        <>
            {discussion.name && <DocumentTitle title={discussion.name} />}
            <ModerationAdminLayout
                title={
                    <>
                        <BackLink className={classes.backlink} />
                        {discussion ? <Translate source="Reports for <0/>" c0={discussion.name} /> : t("Loading...")}
                    </>
                }
                secondaryBar={
                    <>
                        <PostRevisionPicker postRevisionOptions={postRevisions} />
                        <Row gap={16}>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    const { insertUserID: userID, url } = discussion ?? {};
                                    if (userID && url) {
                                        setAuthorMessage({ userID, url });
                                    }
                                }}
                            >
                                {t("Message Post Author")}
                            </Button>

                            <>
                                {discussion && (
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => {
                                            resolveMutation.mutate({
                                                discussionID: discussion.discussionID,
                                                internalStatusID: discussion.resolved
                                                    ? TriageInternalStatus.UNRESOLVED
                                                    : TriageInternalStatus.RESOLVED,
                                            });
                                        }}
                                    >
                                        {resolveMutation.isLoading ? (
                                            <ButtonLoader />
                                        ) : discussion.resolved ? (
                                            t("Unresolve")
                                        ) : (
                                            t("Resolve")
                                        )}
                                    </Button>
                                )}
                            </>

                            {(hasPermission("community.moderate") ||
                                hasPermission("posts.moderate", {
                                    mode: PermissionMode.RESOURCE_IF_JUNCTION,
                                    resourceType: "category",
                                    resourceID: discussion?.categoryID,
                                })) &&
                                (!escalation ? (
                                    <Button
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                        onClick={() => {
                                            setEscalationModalVisibility(true);
                                        }}
                                    >
                                        {t("Escalate")}
                                    </Button>
                                ) : (
                                    <LinkAsButton buttonType={ButtonTypes.TEXT_PRIMARY} to={escalation.sourceUrl!}>
                                        {t("View Escalation")}
                                    </LinkAsButton>
                                ))}
                        </Row>
                    </>
                }
                rightPanel={
                    <>
                        <ReportHistoryList
                            reports={reports}
                            activeReportIDs={postRevisions.selectedRevision?.reportIDs ?? []}
                            onReportSelected={(report) => {
                                postRevisions.setReportRevisionActive(report.reportID);
                            }}
                        />
                    </>
                }
                content={
                    <>
                        <section className={classes.layout}>
                            {discussion && (
                                <div>
                                    {postRevisions.selectedRevision ? (
                                        <PostDetail activeRevisionOption={postRevisions.selectedRevision} />
                                    ) : (
                                        <Message error={{ message: t("No post revision could be found") }} />
                                    )}
                                </div>
                            )}
                            <div>
                                <>
                                    {filteredDiscussionComments.all.length > 0 && discussion ? (
                                        <PageBox
                                            options={{
                                                borderType: BorderType.NONE,
                                            }}
                                        >
                                            <Tabs
                                                key={filteredDiscussionComments.all.length}
                                                tabType={TabsTypes.BROWSE}
                                                data={makeTabData(filteredDiscussionComments)}
                                                activeTab={selectedTabIndex}
                                                setActiveTab={setSelectedTabIndex}
                                            />
                                        </PageBox>
                                    ) : (
                                        <>
                                            <p className={reportHistoryListClasses().emptyHeadline}>
                                                {t("There are currently no comments on this post")}
                                            </p>
                                            <p className={reportHistoryListClasses().emptyByline}>
                                                {t("All comments on this post will appear here")}
                                            </p>
                                        </>
                                    )}
                                </>
                            </div>
                        </section>
                    </>
                }
            />
            <EscalateModal
                recordType={"discussion"}
                recordID={discussion.discussionID}
                escalationType={reports && reports.length > 0 ? "report" : "record"}
                report={reports && reports.length > 0 ? reports[0] : null}
                record={reports && reports.length === 0 ? discussion : null}
                isVisible={escalationModalVisibility}
                onSuccess={async () => {
                    await queryClient.invalidateQueries(["discussion"]);
                }}
                onClose={() => {
                    setEscalationModalVisibility(false);
                }}
            />
            <MessageAuthorModal
                messageInfo={authorMessage}
                isVisible={!!authorMessage}
                onClose={() => setAuthorMessage(null)}
            />
        </>
    );
}

function TriageDetailPage(props: IProps) {
    const discussionQuery = useDiscussionQuery(props.match.params.recordID, ["category", "attachments", "status.log"]);
    const reportsQuery = useReportsQuery("discussion", props.match.params.recordID);

    return (
        <QueryLoader
            query={discussionQuery}
            query2={reportsQuery}
            success={(discussion, reports) => {
                return <TriageDetailPageImpl discussion={discussion} reports={reports} />;
            }}
        />
    );
}

export default TriageDetailPage;
