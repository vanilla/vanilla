/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright Vanilla Forum Inc. 2009-2024
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import DateTime from "@library/content/DateTime";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ProfileLink from "@library/navigation/ProfileLink";
import { usePostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { postReactionsLogClasses } from "@library/postReactions/PostReactionsLog.classes";
import { cx } from "@library/styles/styleShim";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { useState } from "react";

export function PostReactionsLog() {
    const classes = postReactionsLogClasses();
    const { reactionLog, toggleReaction } = usePostReactionsContext();

    if (!reactionLog || !reactionLog.length) {
        return <p className={classes.noReactions}>{t("No Reactions Yet")}</p>;
    }

    return (
        <ul className={classes.root}>
            {reactionLog.map(({ reactionType, user, dateInserted }) => (
                <li key={[reactionType.tagID, user.userID].join("-")} className={classes.reactionLogItem}>
                    <DateTime timestamp={dateInserted} className={classes.reactionLogDate} />
                    <ProfileLink userFragment={user} isUserCard className={classes.reactionLogUser} />
                    <span className={classes.reactionLogName}>{reactionType.name}</span>
                    <ToolTip label={t("Remove reaction")}>
                        <span>
                            <Button
                                buttonType={ButtonTypes.ICON_COMPACT}
                                className={classes.reactionLogDelete}
                                title={t("Remove reaction")}
                                onClick={async () => {
                                    if (toggleReaction) {
                                        await toggleReaction({
                                            reaction: reactionType as IReaction,
                                            user,
                                            deleteOnly: true,
                                        });
                                    }
                                }}
                            >
                                <Icon icon="data-trash" />
                            </Button>
                        </span>
                    </ToolTip>
                </li>
            ))}
        </ul>
    );
}

export function PostReactionsLogAsModal(props: { className?: string }) {
    const { recordID, recordType } = usePostReactionsContext();
    const classes = postReactionsLogClasses();
    const [modalOpen, setModalOpen] = useState<boolean>(false);
    const titleID = ["reactionLog", recordType, recordID].join("-");

    const closeModal = () => setModalOpen(false);

    return (
        <>
            <Button
                buttonType={ButtonTypes.TEXT}
                className={cx(classes.reactionLogTrigger, props.className)}
                title={t("View Reactions Log")}
                onClick={() => setModalOpen(true)}
            >
                <Icon icon="reaction-log" />
                Log
            </Button>
            <Modal isVisible={modalOpen} exitHandler={closeModal} size={ModalSizes.MEDIUM} titleID={titleID}>
                <Frame
                    header={<FrameHeader titleID={titleID} closeFrame={closeModal} title={t("Reactions")} />}
                    body={
                        <FrameBody>
                            <PostReactionsLog />
                        </FrameBody>
                    }
                />
            </Modal>
        </>
    );
}
