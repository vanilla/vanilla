/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { triageListItemClasses } from "@dashboard/moderation/components/TriageListItem.classes";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import UserContent from "@library/content/UserContent";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { useToast } from "@library/features/toaster/ToastContext";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MetaIcon, MetaItem, MetaProfile, Metas } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import { Icon } from "@vanilla/icons";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { useDashboardSectionActions } from "@dashboard/DashboardSectionHooks";

interface IProps {
    discussion: IDiscussion;
    onEscalate: (discussion: IDiscussion) => void;
    onMessageAuthor: (authorID: IUser["userID"], recordUrl: IDiscussion["url"]) => void;
}

export function TriageListItem(props: IProps) {
    const { discussion, onEscalate } = props;
    const classes = triageListItemClasses();
    const isResolved = discussion.internalStatusID?.toString() == TriageInternalStatus.RESOLVED;
    const toast = useToast();
    const queryClient = useQueryClient();
    const { fetchDashboardSections } = useDashboardSectionActions();
    const resolveMutation = useMutation({
        mutationFn: (options: {
            discussionID: IDiscussion["discussionID"];
            internalStatusID: TriageInternalStatus;
        }) => {
            const { discussionID, internalStatusID } = options;
            return apiv2.put(`/discussions/${discussionID}/status`, {
                internalStatusID: internalStatusID,
            });
        },
        onSuccess() {
            queryClient.invalidateQueries(["triageItems"]);
            fetchDashboardSections();
            toast.addToast({
                autoDismiss: true,
                body: "Post marked as resolved.",
            });
        },
    });

    const escalation = discussion.attachments?.find((item) => item.attachmentType === "vanilla-escalation");
    const detailUrl = `/dashboard/content/triage/${discussion.discussionID}`;

    return (
        <div className={classes.container}>
            <div className={classes.main}>
                <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                    <ListItem
                        as={"div"}
                        url={detailUrl}
                        name={discussion.name}
                        nameClassName={communityManagementPageClasses().listItemLink}
                        description={<UserContent className={classes.description} content={discussion.body!} />}
                        truncateDescription={false}
                        metas={
                            <>
                                <Metas>
                                    <MetaIcon
                                        icon={isResolved ? "cmd-approve" : "cmd-alert"}
                                        aria-label={isResolved ? t("Resolved") : t("Unresolved")}
                                    />
                                    <MetaItem flex>
                                        <Translate
                                            source="Posted by <0/> in <1/>"
                                            c0={<MetaProfile user={discussion.insertUser ?? deletedUserFragment()} />}
                                            c1={
                                                <SmartLink
                                                    to={`${discussion.category?.url}`}
                                                    className={metasClasses().metaLink}
                                                >
                                                    {discussion.category?.name}
                                                </SmartLink>
                                            }
                                        />
                                    </MetaItem>
                                    <MetaItem>
                                        <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                                        <DateTime timestamp={discussion.dateInserted}></DateTime>
                                    </MetaItem>
                                </Metas>
                                {discussion.reportMeta && discussion.reportMeta?.countReports > 0 && (
                                    <Metas className={classes.metaLine}>
                                        <AssociatedReportMetas
                                            reasons={discussion.reportMeta.reportReasons}
                                            countReports={discussion.reportMeta.countReports}
                                            dateLastReport={discussion.reportMeta.dateLastReport}
                                        />
                                    </Metas>
                                )}
                            </>
                        }
                    />
                </ListItemContext.Provider>

                <div className={classes.quickActions}>
                    {!isResolved && (
                        <ToolTip label={t("Resolve post")}>
                            <Button
                                buttonType={ButtonTypes.ICON_COMPACT}
                                onClick={() => {
                                    resolveMutation.mutate({
                                        discussionID: discussion.discussionID,
                                        internalStatusID: TriageInternalStatus.RESOLVED,
                                    });
                                }}
                            >
                                {resolveMutation.isLoading ? <ButtonLoader /> : <Icon icon="cmd-dismiss" />}
                            </Button>
                        </ToolTip>
                    )}
                    <ToolTip label={t("View post in community")}>
                        <span>
                            <LinkAsButton buttonType={ButtonTypes.ICON_COMPACT} to={discussion.url} target="_blank">
                                <Icon icon="meta-external" />
                            </LinkAsButton>
                        </span>
                    </ToolTip>
                </div>
            </div>
            <div className={classes.attachments}>
                {(discussion.attachments ?? []).length > 0 &&
                    discussion.attachments?.map((attachment) => (
                        <ReadableIntegrationContextProvider
                            key={attachment.attachmentID}
                            attachmentType={attachment.attachmentType}
                        >
                            <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                        </ReadableIntegrationContextProvider>
                    ))}
            </div>
            <footer className={classes.footer}>
                <div className={classes.actions}>
                    <LinkAsButton to={detailUrl} buttonType={ButtonTypes.TEXT}>
                        {t("View Details")}
                    </LinkAsButton>
                    <span style={{ flex: 1 }} />
                    <Button
                        buttonType={ButtonTypes.TEXT}
                        onClick={() => props.onMessageAuthor(discussion.insertUserID, discussion.url)}
                    >
                        {t("Message Post Author")}
                    </Button>
                    {!escalation ? (
                        <Button
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => {
                                onEscalate(discussion);
                            }}
                        >
                            {t("Escalate")}
                        </Button>
                    ) : (
                        <LinkAsButton buttonType={ButtonTypes.TEXT_PRIMARY} to={escalation.sourceUrl!}>
                            {t("View Escalation")}
                        </LinkAsButton>
                    )}
                </div>
            </footer>
        </div>
    );
}
