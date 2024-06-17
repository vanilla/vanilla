import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { escalationClasses } from "@dashboard/moderation/components/EscalationList.classes";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
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
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { StackingContextProvider } from "@vanilla/react-utils";

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
                            <Notice>{escalation.status}</Notice>
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
                                    <DropDownItemButton onClick={() => null}>Create Zendesk Ticket</DropDownItemButton>
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

            <div className={cx(classes.listItemContainer, classes.actionBar)}>
                <Button buttonType={ButtonTypes.TEXT}>
                    {escalation.recordIsLive ? t("Remove Post") : t("Restore Post")} <DownTriangleIcon />
                </Button>
                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => onMessageAuthor && onMessageAuthor(escalation.recordUserID, escalation.recordUrl)}
                >
                    Message Author
                </Button>
                <Button buttonType={ButtonTypes.TEXT}>Create Zendesk Ticket</Button>
                <span style={{ flex: 1 }}></span>
                <EscalationAssignee escalation={escalation} inCard />
            </div>
        </div>
    );
}
