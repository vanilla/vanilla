/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import {
    useEscalationCommentsQuery,
    useEscalationMutation,
    useEscalationQuery,
    useRevisionOptions,
} from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { EscalationActions } from "@dashboard/moderation/components/EscalationActions";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { EscalationCommentEditor } from "@dashboard/moderation/components/EscalationCommentEditor";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { PostDetail } from "@dashboard/moderation/components/PostDetail";
import { ReportHistoryList } from "@dashboard/moderation/components/ReportHistoryList";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { PostRevisionProvider, usePostRevision } from "@dashboard/moderation/PostRevisionContext";
import { cx } from "@emotion/css";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { Metas } from "@library/metas/Metas";
import DocumentTitle from "@library/routing/DocumentTitle";
import BackLink from "@library/routing/links/BackLink";
import { Sort } from "@library/sort/Sort";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { QueryClient, useQueryClient } from "@tanstack/react-query";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useCollisionDetector } from "@vanilla/react-utils";
import { useEffect, useRef, useState } from "react";

import { RouteComponentProps } from "react-router";

interface IProps extends RouteComponentProps<{ escalationID: string }> {
    escalationID: IEscalation["escalationID"];
    escalation?: IEscalation;
}

const discussion = LayoutEditorPreviewData.discussion();

function EscalationsDetailPageImpl(props: IProps) {
    const { escalationID, escalation } = props;
    const cmdClasses = communityManagementPageClasses();
    const classes = detailPageClasses();
    const postRevision = usePostRevision();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const { options, selectedOption } = useRevisionOptions();

    const escalationMutation = useEscalationMutation(escalationID);
    const comments = useEscalationCommentsQuery(escalationID);

    const queryClient = useQueryClient();
    const invalidateQueries = async () => {
        queryClient.invalidateQueries(["escalations"]);
        queryClient.invalidateQueries(["escalations", escalationID]);
        queryClient.invalidateQueries(["escalationComments", escalationID]);
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
            <AdminLayout
                title={
                    <div className={classes.titleLayout}>
                        <BackLink className={classes.backlink} />
                        {escalation && escalation.name.length > 0 && (
                            <div className={classes.editableTitleLayout}>
                                <AutoWidthInput
                                    required
                                    onChange={(event) => setTitle(event.target.value)}
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
                                        <Icon icon={"data-pencil"} />
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
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
                leftPanel={!isCompact && <ModerationNav />}
                rightPanel={
                    <>
                        <EscalationActions escalationID={escalationID} />
                        <ReportHistoryList />
                    </>
                }
                content={
                    <>
                        <section className={cx(cmdClasses.secondaryTitleBar, classes.secondaryTitleBarTop)}>
                            <span className={cmdClasses.secondaryTitleBarStart}>
                                {options.length > 1 && (
                                    <Sort
                                        sortID={"postRevision"}
                                        sortLabel={t("Revision: ")}
                                        sortOptions={options}
                                        selectedSort={selectedOption}
                                        onChange={(revision) => {
                                            postRevision.setActiveRevision(+revision.value);
                                        }}
                                    />
                                )}
                            </span>
                            <span className={cmdClasses.secondaryTitleBarButtons}>
                                <label className={classes.assigneeDropdown}>
                                    <span>{t("Assignee: ")}</span>
                                    {escalation && (
                                        <EscalationAssignee
                                            escalation={escalation}
                                            className={classes.assigneeOverrides}
                                            autoCompleteClasses={classes.autoCompleteOverrides}
                                        />
                                    )}
                                </label>
                            </span>
                        </section>
                        <section className={classes.layout}>
                            {postRevision.mostRecentRevision && (
                                <div>
                                    <PostDetail />
                                </div>
                            )}
                            {escalation && (escalation.attachments ?? []).length > 0 && (
                                <div>
                                    <DashboardFormSubheading>{t("Escalation Attachments")}</DashboardFormSubheading>

                                    {escalation.attachments?.map((attachment) => (
                                        <ReadableIntegrationContextProvider
                                            key={attachment.attachmentID}
                                            attachmentType={attachment.attachmentType}
                                        >
                                            <DiscussionAttachment
                                                key={attachment.attachmentID}
                                                attachment={attachment}
                                            />
                                        </ReadableIntegrationContextProvider>
                                    ))}
                                </div>
                            )}
                            {escalation && (
                                <div>
                                    <DashboardFormSubheading>{t("Internal Comments")}</DashboardFormSubheading>
                                    {comments.data && comments?.data?.length < 1 && (
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
                                        {comments?.data?.map((comment) => {
                                            return (
                                                <CommentThreadItem
                                                    key={comment.commentID}
                                                    comment={comment}
                                                    discussion={discussion}
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
                            )}
                            <div>
                                <DashboardFormSubheading>{t("Add a comment")}</DashboardFormSubheading>
                                <div className={classes.commentsWrapper}>
                                    <EscalationCommentEditor escalationID={escalationID} />
                                </div>
                            </div>
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
    return (
        <PostRevisionProvider recordType={recordType} recordID={recordID}>
            <EscalationsDetailPageImpl
                {...props}
                escalationID={props.match.params.escalationID}
                escalation={escalationQuery.data}
            />
            ;
        </PostRevisionProvider>
    );
}

export default EscalationsDetailPage;
