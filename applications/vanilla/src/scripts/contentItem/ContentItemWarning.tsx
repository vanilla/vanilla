/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";
import Message from "@library/messages/Message";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useState } from "react";
import ModalSizes from "@library/modal/ModalSizes";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import SmartLink from "@library/routing/links/SmartLink";
import UserContent from "@library/content/UserContent";
import { cx } from "@emotion/css";
import { ToolTip } from "@library/toolTip/ToolTip";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";

export interface IPostWarning {
    body: string;
    dateInserted: string;
    format: string;
    insertUser?: IUserFragment;
    moderatorNote: string;
    points?: number;
    recordID?: RecordID;
    recordType?: "comment" | "discussion";
    rule?: any;
    type?: string;
    user: IUserFragment;
    userNoteID: number;
    conversationID: number;
    warningType?: {
        warningTypeID: number;
        name: string;
        description: string;
    };
}

interface IProps {
    warning: IPostWarning;
    recordName: string;
    recordUrl: string;
    moderatorNoteVisible?: boolean;
    forceModalVisibility?: boolean;
}

export function ContentItemWarning(props: IProps) {
    const { warning, recordName, recordUrl, moderatorNoteVisible } = props;
    const classes = ContentItemClasses();
    const [warningModalIsVisible, setWarningModalIsVisible] = useState(props.forceModalVisibility || false);

    return (
        <>
            <div className={classes.aboveMainContent}>
                <Message
                    icon={<Icon icon="notification-alert" />}
                    stringContents=""
                    type="warning"
                    contents={
                        <p>
                            {
                                <Translate
                                    source="Moderator issued a <0/> to <1/>."
                                    c0={
                                        <Button
                                            buttonType={ButtonTypes.TEXT_PRIMARY}
                                            onClick={() => setWarningModalIsVisible(!warningModalIsVisible)}
                                        >
                                            {t("warning")}
                                        </Button>
                                    }
                                    c1={warning.user.name}
                                />
                            }
                        </p>
                    }
                />
                <Modal
                    isVisible={warningModalIsVisible}
                    exitHandler={() => setWarningModalIsVisible(false)}
                    size={ModalSizes.MEDIUM}
                    titleID={"post_warning_modal"}
                    className={classes.postWarningModal}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"post_warning_modal_title"}
                                closeFrame={() => setWarningModalIsVisible(false)}
                                title={t("Warning")}
                            />
                        }
                        body={
                            <FrameBody className={frameBodyClasses().root}>
                                <div
                                    className={cx(classes.postWarningTopSpace(16), classes.postWarningBottomSpace(16))}
                                >
                                    <div className={classes.postWarningBottomSpace(12)}>
                                        <div className={cx(classes.postWarningBottomSpace(), classes.postWarningBold)}>
                                            {t("Warning Type")}
                                        </div>
                                        <div>
                                            {warning.warningType?.name}
                                            {warning.warningType?.description &&
                                                ` - ${warning.warningType.description}`}
                                        </div>
                                    </div>
                                    <div className={cx(classes.postWarningBottomSpace(12), classes.postWarningBold)}>
                                        <div className={classes.postWarningBottomSpace()}>{t("Moderator Message")}</div>
                                        <SmartLink to={`/messages/${warning.conversationID}#latest`}>
                                            {t("View Message")}
                                        </SmartLink>
                                    </div>
                                    <div className={cx(classes.postWarningBottomSpace(12), classes.postWarningBold)}>
                                        <div className={classes.postWarningBottomSpace()}>{t("Warned Post")}</div>
                                        <SmartLink to={recordUrl}>{recordName}</SmartLink>
                                    </div>
                                    <div className={classes.postWarningBottomSpace(12)}>
                                        <div className={classes.postWarningBold}>{t("Warning Content")}</div>
                                        <UserContent
                                            content={warning.body}
                                            className={classes.postWarningTopSpace(8)}
                                        />
                                    </div>
                                    {moderatorNoteVisible && warning.moderatorNote && warning.moderatorNote !== "" && (
                                        <div>
                                            <div className={cx(classes.postWarningBold, classes.postWarningFlex)}>
                                                {t("Internal Notes")}
                                                <ToolTip
                                                    label={t(
                                                        "This information will only be shown to users with permission to view internal info.",
                                                    )}
                                                >
                                                    <span>
                                                        <Icon icon="profile-crown" />
                                                    </span>
                                                </ToolTip>
                                            </div>
                                            {warning.moderatorNote}
                                        </div>
                                    )}
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <>
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => setWarningModalIsVisible(false)}
                                        className={frameFooterClasses().actionButton}
                                    >
                                        {t("Close")}
                                    </Button>
                                </>
                            </FrameFooter>
                        }
                    />
                </Modal>
            </div>
        </>
    );
}
