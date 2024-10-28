/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useReactionLog } from "@library/postReactions/PostReactions.hooks";
import { usePostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { PostReactionsLog } from "@library/postReactions/PostReactionsLog";
import { t } from "@vanilla/i18n";
import { useEffect } from "react";

export interface PostReactionsModalProps {
    visibility: boolean;
    onVisibilityChange(visibility: boolean): void;
}

export default function PostReactionsModalImpl(props: PostReactionsModalProps) {
    const { visibility, onVisibilityChange } = props;
    const { recordID, recordType } = usePostReactionsContext();
    const titleID = ["reactionLog", recordType, recordID].join("-");
    const reactionLog = useReactionLog({ recordID, recordType });

    // Fetch log on mount
    useEffect(() => {
        reactionLog.isStale && reactionLog.refetch();
    }, []);

    return (
        <Modal
            isVisible={visibility}
            exitHandler={() => onVisibilityChange(false)}
            size={ModalSizes.MEDIUM}
            titleID={titleID}
        >
            <Frame
                header={
                    <FrameHeader
                        titleID={titleID}
                        closeFrame={() => onVisibilityChange(false)}
                        title={t("Reactions")}
                    />
                }
                body={
                    <FrameBody>
                        <PostReactionsLog />
                    </FrameBody>
                }
            />
        </Modal>
    );
}
