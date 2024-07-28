import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { escalationClasses } from "@dashboard/moderation/components/EscalationList.classes";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { EscalationStatus } from "@dashboard/moderation/components/escalationStatuses";
import { cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    ReadableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
    WriteableIntegrationContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DownTriangleIcon } from "@library/icons/common";
import PageHeading from "@library/layout/PageHeading";
import { Metas } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import SmartLink from "@library/routing/links/SmartLink";
import { useQueryClient } from "@tanstack/react-query";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { StackingContextProvider } from "@vanilla/react-utils";
import { useRef } from "react";

interface IProps {
    escalations: IEscalation[];
}

export function EscalationList(props: IProps) {
    const classes = escalationClasses();
    return (
        <div>
            <div className={classes.list}>
                {props.escalations.map((escalation) => (
                    <EscalationListItem key={escalation.escalationID} escalation={escalation} />
                ))}
            </div>
        </div>
    );
}

export function EscalationListItem(props: {
    escalation: IEscalation;
    onMessageAuthor?: (authorID: IUser["userID"], recordUrl: IEscalation["recordUrl"]) => void;
}) {
    const { escalation, onMessageAuthor } = props;
    const classes = escalationClasses();
    const writeableIntegrations = useWriteableAttachmentIntegrations();
    const queryClient = useQueryClient();

    return (
        <div className={classes.listItem}>
            <div className={classes.listItemContainer}>
                <PageHeading
                    includeBackLink={false}
                    depth={4}
                    title={
                        <div className={classes.title}>
                            <SmartLink
                                className={classes.titleContents}
                                to={`/dashboard/content/escalations/${escalation.escalationID}`}
                            >
                                {escalation.name}
                            </SmartLink>
                            <EscalationStatus status={escalation.status} />
                        </div>
                    }
                    actions={
                        <StackingContextProvider>
                            <DropDown
                                className={classes.moreActions}
                                buttonContents={<Icon icon={"navigation-circle-ellipsis"} />}
                                flyoutType={FlyoutType.LIST}
                                asReachPopover
                            >
                                <DropDownSection title={t("Actions")}>
                                    <DropDownItemButton onClick={() => null}>Assign</DropDownItemButton>
                                    <DropDownItemButton onClick={() => null}>Remove Post</DropDownItemButton>
                                    <DropDownItemButton onClick={() => null}>Message Author</DropDownItemButton>
                                </DropDownSection>
                                <DropDownSection title={t("Integrations")}>
                                    {writeableIntegrations
                                        .filter(({ recordTypes }) => recordTypes.includes("escalation"))

                                        .map(({ attachmentType }) => {
                                            return (
                                                <WriteableIntegrationContextProvider
                                                    key={attachmentType}
                                                    recordType="escalation"
                                                    attachmentType={attachmentType}
                                                    recordID={escalation.escalationID}
                                                >
                                                    <IntegrationButtonAndModal
                                                        onSuccess={() => {
                                                            queryClient.invalidateQueries(["escalations"]);
                                                            return Promise.resolve();
                                                        }}
                                                    />
                                                </WriteableIntegrationContextProvider>
                                            );
                                        })}
                                </DropDownSection>
                            </DropDown>
                        </StackingContextProvider>
                    }
                />
                <Metas>
                    <EscalationMetas escalation={escalation} />
                </Metas>
                {escalation.dateLastReport && (
                    <AssociatedReportMetas
                        reportingUsers={escalation.reportUsers}
                        countReports={escalation.countReports}
                        reasons={escalation.reportReasons}
                        dateLastReport={escalation.dateLastReport}
                    />
                )}
            </div>
            <div className={classes.listItemContainer}>
                {(escalation.attachments ?? []).length > 0 &&
                    escalation.attachments?.map((attachment) => (
                        <ReadableIntegrationContextProvider
                            key={attachment.attachmentID}
                            attachmentType={attachment.attachmentType}
                        >
                            <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                        </ReadableIntegrationContextProvider>
                    ))}
            </div>
            <div className={cx(classes.listItemContainer, classes.actionBar)}>
                <EscalationAssignee escalation={escalation} inCard />
                <span style={{ flex: 1 }}></span>

                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => onMessageAuthor && onMessageAuthor(escalation.recordUserID, escalation.recordUrl)}
                >
                    Message Author
                </Button>
                <Button buttonType={ButtonTypes.TEXT_PRIMARY}>
                    {escalation.recordIsLive ? t("Remove Post") : t("Restore Post")} <DownTriangleIcon />
                </Button>
            </div>
        </div>
    );
}
