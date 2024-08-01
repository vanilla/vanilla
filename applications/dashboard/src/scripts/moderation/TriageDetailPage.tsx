import { IComment } from "@dashboard/@types/api/comment";
import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { useRevisionOptions } from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { PostRevisionProvider, usePostRevision } from "@dashboard/moderation/PostRevisionContext";
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
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import ButtonLoader from "@library/loaders/ButtonLoader";
import DocumentTitle from "@library/routing/DocumentTitle";
import BackLink from "@library/routing/links/BackLink";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { Sort } from "@library/sort/Sort";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { labelize, notEmpty } from "@vanilla/utils";
import { useMemo, useState } from "react";
import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ recordID: string }> {}

function TriageDetailPageImpl(props: IProps) {
    const recordID = props.match.params.recordID;

    const cmdClasses = communityManagementPageClasses();
    const classes = detailPageClasses();
    const postRevision = usePostRevision();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const { options, selectedOption } = useRevisionOptions();

    const [selectedTabIndex, setSelectedTabIndex] = useState(0);
    const [escalationModalVisibility, setEscalationModalVisibility] = useState(false);

    const queryClient = useQueryClient();
    const resolveMutation = useMutation({
        mutationFn: (options: { discussionID: string; internalStatusID: TriageInternalStatus }) => {
            const { discussionID, internalStatusID } = options;
            return apiv2.put(`/discussions/${discussionID}/status`, {
                internalStatusID: internalStatusID,
            });
        },
        onSuccess() {
            queryClient.invalidateQueries(["post", recordID]);
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

    const filteredDiscussionComments = useMemo(() => {
        if (postRevision.mostRecentRevision && discussionComments.data) {
            const dateUpdated = postRevision.mostRecentRevision?.internalStatus?.log?.dateUpdated;
            if (dateUpdated) {
                // Remove me, when API is fixed
                // const resolutionDate = `${dateUpdated.split(" ")[0]}T${dateUpdated.split(" ")[1]}+00:00`;
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
    }, [discussionComments.data, postRevision.mostRecentRevision]);

    const makeTabData = (sortedComments: Record<string, IComment[] | undefined>): ITabData[] => {
        if (postRevision.mostRecentRevision) {
            return Object.entries(sortedComments)
                .map(([key, comments]) => {
                    if (comments?.length !== 0) {
                        return {
                            tabID: key,
                            label: <Translate source={"<0/> Post Comments"} c0={labelize(key)} />,
                            contents: (
                                <>
                                    {comments?.map((comment) => {
                                        return (
                                            <CommentThreadItem
                                                key={comment.commentID}
                                                comment={comment}
                                                discussion={postRevision.mostRecentRevision!}
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
        }
        return [];
    };

    return (
        <ErrorBoundary>
            {postRevision?.mostRecentRevision?.name && <DocumentTitle title={postRevision.mostRecentRevision.name} />}
            <AdminLayout
                title={
                    <>
                        <BackLink className={classes.backlink} />
                        {postRevision.mostRecentRevision ? (
                            <Translate source="Reports for <0/>" c0={postRevision.mostRecentRevision.name} />
                        ) : (
                            t("Loading...")
                        )}
                    </>
                }
                leftPanel={!isCompact && <ModerationNav />}
                rightPanel={
                    <>
                        <ReportHistoryList />
                    </>
                }
                content={
                    <>
                        <section className={cmdClasses.secondaryTitleBar}>
                            <span>
                                {postRevision.reports && postRevision.reports.length > 0 ? (
                                    <Sort
                                        sortID={"postRevision"}
                                        sortLabel={t("Revision: ")}
                                        sortOptions={options}
                                        selectedSort={selectedOption}
                                        onChange={(revision) => {
                                            postRevision.setActiveRevision(+revision.value);
                                        }}
                                    />
                                ) : (
                                    <span>
                                        {t("Revision: ")}
                                        <span>{options?.[0]?.name}</span>
                                    </span>
                                )}
                            </span>
                            <span className={cmdClasses.secondaryTitleBarButtons}>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        const { insertUserID: userID, url } = postRevision.mostRecentRevision ?? {};
                                        if (userID && url) {
                                            setAuthorMessage({ userID, url });
                                        }
                                    }}
                                >
                                    {t("Message Post Author")}
                                </Button>

                                <>
                                    {postRevision.mostRecentRevision && (
                                        <Button
                                            buttonType={ButtonTypes.TEXT}
                                            onClick={() => {
                                                resolveMutation.mutate({
                                                    discussionID: recordID,
                                                    internalStatusID: postRevision?.mostRecentRevision?.resolved
                                                        ? TriageInternalStatus.UNRESOLVED
                                                        : TriageInternalStatus.RESOLVED,
                                                });
                                            }}
                                        >
                                            {resolveMutation.isLoading ? (
                                                <ButtonLoader />
                                            ) : postRevision?.mostRecentRevision?.resolved ? (
                                                t("Unresolve")
                                            ) : (
                                                t("Resolve")
                                            )}
                                        </Button>
                                    )}
                                </>

                                <Button
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    onClick={() => {
                                        setEscalationModalVisibility(true);
                                    }}
                                >
                                    {t("Escalate")}
                                </Button>
                            </span>
                        </section>
                        <section className={classes.layout}>
                            {postRevision.mostRecentRevision && (
                                <div>
                                    <PostDetail />
                                </div>
                            )}
                            <div>
                                <>
                                    {filteredDiscussionComments.all.length > 0 && postRevision.mostRecentRevision ? (
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
                escalationType={postRevision.reports && postRevision.reports.length > 0 ? "report" : "record"}
                report={postRevision.reports && postRevision.reports.length > 0 ? postRevision.reports[0] : null}
                record={
                    postRevision.reports && postRevision.reports.length === 0 ? postRevision.mostRecentRevision : null
                }
                recordType={postRevision.mostRecentRevision?.type}
                isVisible={escalationModalVisibility}
                onClose={() => {
                    setEscalationModalVisibility(false);
                }}
            />
            <MessageAuthorModal
                messageInfo={authorMessage}
                isVisible={!!authorMessage}
                onClose={() => setAuthorMessage(null)}
            />
        </ErrorBoundary>
    );
}

function TriageDetailPage(props: IProps) {
    const recordID = props.match.params.recordID;
    return (
        <PostRevisionProvider recordType={"discussion"} recordID={recordID}>
            <TriageDetailPageImpl {...props} />;
        </PostRevisionProvider>
    );
}

export default TriageDetailPage;
