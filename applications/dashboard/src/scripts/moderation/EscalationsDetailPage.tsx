/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import type { IComment } from "@dashboard/@types/api/comment";
import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import {
    useEscalationCommentsQuery,
    useEscalationMutation,
    useEscalationQuery,
    usePostRevisions,
    useReportsQuery,
} from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { IEscalation, type IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { EscalationActions } from "@dashboard/moderation/components/EscalationActions";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { EscalationCommentEditor } from "@dashboard/moderation/components/EscalationCommentEditor";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { PostDetail } from "@dashboard/moderation/components/PostDetail";
import { ReportHistoryList } from "@dashboard/moderation/components/ReportHistoryList";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { PostRevisionPicker } from "@dashboard/moderation/PostRevisionPicker";
import { cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { MentionsProvider } from "@library/features/users/suggestion/MentionsContext";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Message from "@library/messages/Message";
import { Metas } from "@library/metas/Metas";
import DocumentTitle from "@library/routing/DocumentTitle";
import BackLink from "@library/routing/links/BackLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentItem } from "@vanilla/addon-vanilla/comments/CommentItem";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useEffect, useRef, useState } from "react";

import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ escalationID: string }> {
    escalationID: IEscalation["escalationID"];
}

interface IImplProps {
    escalation: IEscalation;
    reports: IReport[];
    livePost: IDiscussion | IComment | null;
}

function EscalationsDetailPageImpl(props: IImplProps) {
    const { reports, escalation, livePost } = props;
    const { escalationID } = escalation;
    const cmdClasses = communityManagementPageClasses();
    const classes = detailPageClasses();
    const postRevisions = usePostRevisions(reports, livePost);

    const escalationMutation = useEscalationMutation(escalationID);
    const commentsQuery = useEscalationCommentsQuery(escalationID);

    const queryClient = useQueryClient();
    const invalidateQueries = async () => {
        await queryClient.invalidateQueries(["escalations"]);
        await queryClient.invalidateQueries(["escalations", escalationID]);
        await queryClient.invalidateQueries(["escalationComments", escalationID]);
    };

    const editableRef = useRef<HTMLInputElement | null>();

    const focusAndSelectAll = (event?: any) => {
        if (editableRef.current && editableRef.current !== document.activeElement) {
            if (event) event.preventDefault();
            editableRef.current.focus();
            document.execCommand("selectAll");
        }
    };

    const [title, setTitle] = useState(escalation?.name ?? "");

    useEffect(() => {
        escalation && setTitle(escalation.name);
    }, [escalation]);

    const saveTitle = () => {
        escalationMutation.mutate({ payload: { name: title } });
    };

    return (
        <ErrorBoundary>
            {escalation?.name && <DocumentTitle title={escalation?.name} />}
            <ModerationAdminLayout
                title={
                    <div className={classes.titleLayout}>
                        <BackLink className={classes.backlink} fallbackUrl="/dashboard/content/escalations" />
                        {escalation && escalation.name.length > 0 && (
                            <div className={classes.editableTitleLayout}>
                                <AutoWidthInput
                                    required
                                    onChange={(event) => setTitle(event.target.value)}
                                    fontSize={22}
                                    className={cx(autoWidthInputClasses().themeInput, classes.editableTitleInput)}
                                    ref={(ref) => (editableRef.current = ref)}
                                    value={title}
                                    placeholder={t("Enter a title for this escalation")}
                                    disabled={!escalation || escalationMutation.isLoading}
                                    onFocus={(event) => event.target.select()}
                                    autoFocus={false}
                                    onKeyDown={(event) => {
                                        if (event.key === "Enter") {
                                            event.preventDefault();
                                            title !== escalation.name && saveTitle();
                                            (event.target as HTMLElement).blur();
                                        }
                                    }}
                                    onMouseDown={focusAndSelectAll}
                                    maxLength={100}
                                    maximumWidth={Infinity}
                                />
                                {title !== escalation.name ? (
                                    <Button
                                        buttonType={ButtonTypes.ICON}
                                        onClick={() => saveTitle()}
                                        disabled={escalationMutation.isLoading}
                                    >
                                        <Icon icon={"data-checked"} />
                                    </Button>
                                ) : (
                                    <Button buttonType={ButtonTypes.ICON} onClick={focusAndSelectAll}>
                                        <Icon icon={"edit"} />
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                }
                secondaryBar={
                    <>
                        <PostRevisionPicker postRevisionOptions={postRevisions} />

                        <label className={classes.assigneeDropdown}>
                            <span>{t("Assignee")}: </span>
                            {escalation && (
                                <EscalationAssignee
                                    escalation={escalation}
                                    className={classes.assigneeOverrides}
                                    autoCompleteClasses={classes.autoCompleteOverrides}
                                />
                            )}
                        </label>
                    </>
                }
                description={
                    escalation && (
                        <>
                            <Metas>
                                <EscalationMetas escalation={escalation} />
                            </Metas>
                            {escalation.dateLastReport && (
                                <AssociatedReportMetas
                                    countReports={escalation.countReports}
                                    reasons={escalation.reportReasons}
                                    dateLastReport={escalation.dateLastReport}
                                />
                            )}
                        </>
                    )
                }
                rightPanel={
                    <>
                        <EscalationActions escalationID={escalationID} />
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
                            <div>
                                {postRevisions.selectedRevision ? (
                                    <PostDetail activeRevisionOption={postRevisions.selectedRevision} />
                                ) : (
                                    <Message error={{ message: t("No post revision could be found") }} />
                                )}
                            </div>
                            {escalation && (escalation.attachments ?? []).length > 0 && (
                                <div>
                                    <DashboardFormSubheading>{t("Escalation Attachments")}</DashboardFormSubheading>

                                    {escalation.attachments?.map((attachment) => (
                                        <ReadableIntegrationContextProvider
                                            key={attachment.attachmentID}
                                            attachmentType={attachment.attachmentType}
                                        >
                                            <ContentItemAttachment
                                                key={attachment.attachmentID}
                                                attachment={attachment}
                                            />
                                        </ReadableIntegrationContextProvider>
                                    ))}
                                </div>
                            )}
                            <QueryLoader
                                query={commentsQuery}
                                loader={
                                    <>
                                        <LoadingRectangle height={20} width={180} />
                                        <LoadingSpacer height={20} />
                                        <LoadingRectangle height={60} />
                                        <LoadingSpacer height={20} />
                                        <LoadingRectangle height={60} />
                                        <LoadingSpacer height={20} />
                                        <LoadingRectangle height={60} />
                                    </>
                                }
                                success={(comments) => {
                                    return (
                                        <>
                                            <div>
                                                <DashboardFormSubheading>
                                                    {t("Internal Comments")}
                                                </DashboardFormSubheading>
                                                {comments.length < 1 && (
                                                    <p>
                                                        {t(
                                                            "There are no internal comments yet. Create a comment from the box below.",
                                                        )}
                                                    </p>
                                                )}
                                                <PageBox
                                                    options={{
                                                        borderType: BorderType.NONE,
                                                    }}
                                                >
                                                    {comments.map((comment) => {
                                                        return (
                                                            <CommentItem
                                                                key={comment.commentID}
                                                                comment={comment}
                                                                onMutateSuccess={invalidateQueries}
                                                                userPhotoLocation={"header"}
                                                                boxOptions={{
                                                                    borderType: BorderType.SEPARATOR,
                                                                }}
                                                                isInternal
                                                            />
                                                        );
                                                    })}
                                                </PageBox>
                                            </div>
                                            <div>
                                                <DashboardFormSubheading>{t("Add a comment")}</DashboardFormSubheading>
                                                <div className={classes.commentsWrapper}>
                                                    <MentionsProvider recordType={"escalation"} recordID={escalationID}>
                                                        <EscalationCommentEditor escalationID={escalationID} />
                                                    </MentionsProvider>
                                                </div>
                                            </div>
                                        </>
                                    );
                                }}
                            />
                        </section>
                    </>
                }
            />
        </ErrorBoundary>
    );
}

function EscalationsDetailPage(props: IProps) {
    const escalationQuery = useEscalationQuery(props.match.params.escalationID);
    const { recordType, recordID } = escalationQuery.data ?? {};
    const reportsQuery = useReportsQuery(recordType ?? null, recordID ?? null);
    const livePostQuery = useQuery({
        queryKey: ["livePost", recordType, recordID],
        queryFn: async () => {
            try {
                switch (recordType) {
                    case "discussion": {
                        const discussion = await apiv2.get(`/discussions/${recordID}`, {
                            params: {
                                expand: ["category", "attachments", "status.log"],
                            },
                        });
                        return discussion.data;
                    }
                    case "comment": {
                        const comment = await apiv2.get(`/comments/${recordID}`, {
                            params: {
                                expand: ["attachments"],
                                quoteParent: false,
                            },
                        });
                        return comment.data;
                    }
                    default:
                        return null;
                }
            } catch (error) {
                // This is totally possible, post is likely deleted.
                return null;
            }
        },
        enabled: recordType != null && recordID != null,
    });

    return (
        <QueryLoader
            query={escalationQuery}
            query2={reportsQuery}
            query3={livePostQuery}
            success={(escalation, reports, livePost) => {
                return <EscalationsDetailPageImpl escalation={escalation} reports={reports} livePost={livePost} />;
            }}
        />
    );
}

export default EscalationsDetailPage;
