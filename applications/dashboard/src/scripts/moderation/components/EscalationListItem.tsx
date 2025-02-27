import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { escalationClasses } from "@dashboard/moderation/components/EscalationListItem.classes";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { IMessageInfo } from "@dashboard/moderation/components/MessageAuthorModal";
import { EscalationStatus } from "@dashboard/moderation/components/escalationStatuses";
import { cx } from "@emotion/css";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    ReadableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
    WriteableIntegrationContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import PageHeading from "@library/layout/PageHeading";
import { Metas } from "@library/metas/Metas";
import SmartLink from "@library/routing/links/SmartLink";
import { useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { StackingContextProvider } from "@vanilla/react-utils";
import LinkAsButton from "@library/routing/LinkAsButton";
import { IAttachmentIntegration } from "@library/features/discussions/integrations/Integrations.types";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";

export function EscalationListItem(props: {
    escalation: IEscalation;
    onMessageAuthor: (messageInfo: IMessageInfo) => void;
    onRecordVisibilityChange: (recordIsLive: boolean) => void;
    onAttachmentCreated?: (attachmentType: IAttachmentIntegration) => void;
}) {
    const { escalation, onMessageAuthor, onRecordVisibilityChange } = props;
    const classes = escalationClasses();
    const writeableIntegrations = useWriteableAttachmentIntegrations();
    const queryClient = useQueryClient();
    const url = props.escalation.url;

    return (
        <div className={classes.listItem}>
            <div className={classes.listItemContainer}>
                <PageHeading
                    includeBackLink={false}
                    depth={4}
                    title={
                        <div className={classes.title}>
                            <SmartLink className={classes.titleContents} to={url}>
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
                                buttonType={ButtonTypes.ICON_COMPACT}
                                asReachPopover
                            >
                                <DropDownSection title={t("Actions")}>
                                    <DropDownItemButton onClick={() => null}>Remove Post</DropDownItemButton>
                                    <DropDownItemButton
                                        onClick={() =>
                                            onMessageAuthor({
                                                userID: escalation.recordUserID,
                                                url: escalation.recordUrl,
                                            })
                                        }
                                    >
                                        Message Author
                                    </DropDownItemButton>
                                </DropDownSection>
                                <DropDownSection title={t("Integrations")}>
                                    {writeableIntegrations
                                        .filter(({ recordTypes }) => recordTypes.includes("escalation"))
                                        .map((attachmentCatalog) => {
                                            const { attachmentType } = attachmentCatalog;
                                            return (
                                                <WriteableIntegrationContextProvider
                                                    key={attachmentType}
                                                    recordType="escalation"
                                                    attachmentType={attachmentType}
                                                    recordID={escalation.escalationID}
                                                >
                                                    <IntegrationButtonAndModal
                                                        onSuccess={() => {
                                                            props.onAttachmentCreated?.(attachmentCatalog);
                                                            void queryClient.invalidateQueries(["escalations"]);
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
                            <ContentItemAttachment key={attachment.attachmentID} attachment={attachment} />
                        </ReadableIntegrationContextProvider>
                    ))}
            </div>
            <div className={cx(classes.listItemContainer, classes.actionBar)}>
                <EscalationAssignee escalation={escalation} inCard />
                <span style={{ flex: 1 }}></span>

                <LinkAsButton to={url} buttonType={ButtonTypes.TEXT}>
                    View Details
                </LinkAsButton>
                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => {
                        onMessageAuthor({ userID: escalation.recordUserID, url: escalation.recordUrl });
                    }}
                >
                    Message Author
                </Button>
                <Button
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                    onClick={() => onRecordVisibilityChange(!escalation.recordIsLive)}
                >
                    {escalation.recordIsLive ? t("Remove Post") : t("Restore Post")}
                </Button>
            </div>
        </div>
    );
}
