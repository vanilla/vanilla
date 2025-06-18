/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState, useEffect } from "react";
import dompurify from "dompurify";
import debounce from "lodash-es/debounce";
import { useQueryClient } from "@tanstack/react-query";
import classNames from "classnames";
import { uuidv4 } from "@vanilla/utils";
import { t } from "@vanilla/i18n";
import ModalSizes from "@library/modal/ModalSizes";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { DotLoader } from "@library/loaders/DotLoader";
import {
    useGetConversation,
    useGetConversations,
    usePostReply,
    useStartConversation,
    usePutMessageReaction,
} from "@library/aiConversations/AiConversations.hooks";
import { ChatInput } from "@library/aiConversations/ChatInputGeneric";
import { IMessage } from "@library/aiConversations/AiConversations.types";
import { aiChatStyles } from "@library/aiConversations/AiChatInterface.styles";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IAiChatInterfaceImplProps {
    conversationID?: number;
}

export function AiChatInterfaceImpl(props: IAiChatInterfaceImplProps) {
    const { conversationID } = props;

    const queryClient = useQueryClient();
    const classes = aiChatStyles();

    // If we have a conversation ID, fetch the messages
    const { query: conversationsQuery, invalidate: invalidateConversation } = useGetConversation(
        {
            conversationID: conversationID ?? 0,
        },
        conversationID !== undefined,
    );
    const { mutateAsync: postConversationReply, isLoading: isReplyLoading } = usePostReply();
    const startConversation = useStartConversation();
    const putReaction = usePutMessageReaction();

    const conversation = conversationsQuery?.data ?? undefined;
    const messages = conversation?.messages;

    // Messages the user has submitted, but haven't been saved in the conversation yet
    // These are only shown until we have the updated conversation after the reply succeeds
    const [localMessages, setLocalMessages] = useState<IMessage[]>([]);

    async function handleSubmitMessage(value: string) {
        if (!value) {
            return;
        }

        const newLocalMessage = {
            messageID: uuidv4(),
            body: value,
            feedback: null,
            confidence: null,
            reaction: null,
        };

        setLocalMessages([...localMessages, newLocalMessage]);

        if (conversation) {
            await postConversationReply({ conversationID: conversation.conversationID, body: value });
            await invalidateConversation();
        } else {
            await startConversation({ body: value });
            await queryClient.invalidateQueries({ queryKey: ["getAiConversations"] });
        }
    }

    // Messages that have been reacted to, but we haven't received the updated conversation yet
    const [pendingLikes, setPendingLikes] = useState<string[]>([]);
    const [pendingDislikes, setPendingDislikes] = useState<string[]>([]);

    async function handleReaction(message: IMessage, reaction: "like" | "dislike") {
        let reactionToSend: "like" | "dislike" | null = reaction;

        const hasPendingLike = pendingLikes?.includes(message.messageID ?? "");
        const hasPendingDislike = pendingDislikes?.includes(message.messageID ?? "");
        const hasSavedLike = message.reaction === "like";
        const hasSavedDislike = message.reaction === "dislike";

        // The current apparent reaction from the user's perspective
        // It may be already saved in the conversation, or pending
        const currentReaction =
            hasPendingLike || hasSavedLike ? "like" : hasPendingDislike || hasSavedDislike ? "dislike" : null;

        // The user is undoing their current reaction
        if (reaction === currentReaction) {
            if (reaction === "like") {
                setPendingLikes(pendingLikes.filter((id) => id !== message.messageID));
            } else {
                setPendingDislikes(pendingDislikes.filter((id) => id !== message.messageID));
            }
            reactionToSend = null;
        }
        // The user is changing to a different reaction
        else if (currentReaction !== null) {
            if (reaction === "like") {
                if (hasPendingDislike) {
                    setPendingDislikes(pendingDislikes.filter((id) => id !== message.messageID));
                }
                setPendingLikes([...pendingLikes, message.messageID ?? ""]);
            } else {
                if (hasPendingLike) {
                    setPendingLikes(pendingLikes.filter((id) => id !== message.messageID));
                }
                setPendingDislikes([...pendingDislikes, message.messageID ?? ""]);
            }
        }
        // The user is liking or disliking a message that has no reaction yet
        else {
            if (reaction === "like") {
                setPendingLikes([...pendingLikes, message.messageID ?? ""]);
            } else {
                setPendingDislikes([...pendingDislikes, message.messageID ?? ""]);
            }
        }

        await debouncedPutReaction({
            conversationID: conversationID ?? 0,
            messageID: message.messageID ?? "",
            reaction: reactionToSend,
        });
    }

    const debouncedPutReaction = debounce(async (reactionData) => {
        await putReaction(reactionData);
        await invalidateConversation();
    }, 800);

    useEffect(() => {
        return () => {
            debouncedPutReaction.cancel();
        };
    }, []);

    return (
        <div className={classes.outerContainer}>
            {conversationsQuery.status === "success" &&
                messages &&
                messages.map((message: IMessage, index) => {
                    const isAssistant = message.user === "Assistant";

                    const hasBeenLiked =
                        // If there are no reactions pending and the saved reaction is like
                        (!pendingLikes.length && !pendingDislikes.length && message.reaction === "like") ||
                        // Or if there is a pending like
                        pendingLikes?.includes(message.messageID ?? "");

                    const hasBeenDisliked =
                        (!pendingLikes.length && !pendingDislikes.length && message.reaction === "dislike") ||
                        pendingDislikes?.includes(message.messageID ?? "");

                    return (
                        <div
                            key={message.messageID}
                            className={classNames(classes.message, {
                                [classes.messageAssistant]: isAssistant,
                                [classes.messageHuman]: !isAssistant,
                            })}
                        >
                            <div dangerouslySetInnerHTML={{ __html: dompurify.sanitize(message.body as string) }} />
                            {isAssistant ? (
                                <div className={classes.reactionButtonContainer}>
                                    <Button
                                        onClick={() => handleReaction(message, "like")}
                                        buttonType={ButtonTypes.ICON_COMPACT}
                                        ariaLabel={t("Like")}
                                        className={classNames({
                                            [classes.reactionButtonActive]: hasBeenLiked,
                                        })}
                                    >
                                        <Icon icon={"reaction-thumbs-up"} size="compact" />
                                    </Button>

                                    <Button
                                        onClick={() => handleReaction(message, "dislike")}
                                        buttonType={ButtonTypes.ICON_COMPACT}
                                        ariaLabel={t("Dislike")}
                                        className={classNames({
                                            [classes.reactionButtonActive]: hasBeenDisliked,
                                        })}
                                    >
                                        <Icon icon={"reaction-thumbs-down"} size="compact" />
                                    </Button>
                                </div>
                            ) : (
                                <></>
                            )}
                        </div>
                    );
                })}

            {localMessages.map((msg) => {
                if (messages?.find((fetchedMessage) => fetchedMessage.body === msg.body)) {
                    return <></>;
                }
                return (
                    <div key={msg.messageID} className={classNames(classes.message, classes.messageHuman)}>
                        <div dangerouslySetInnerHTML={{ __html: dompurify.sanitize(msg.body as string) }} />
                    </div>
                );
            })}

            {conversationID !== undefined && conversationsQuery.status === "loading" && <DotLoader />}

            {isReplyLoading && (
                <div className={classes.message}>
                    <DotLoader />
                </div>
            )}

            <ChatInput onSubmit={handleSubmitMessage} isLoading={!!conversationID && conversationsQuery.isLoading} />
        </div>
    );
}

interface IAiChatModalProps {
    isVisible: boolean;
    onClose: () => void;

    conversationID?: number;

    size?: ModalSizes;
    title?: string;
}

export function AiChatInterfaceModal(props: IAiChatModalProps) {
    const { isVisible, onClose, conversationID, size, title } = props;

    const classes = aiChatStyles();

    // if we don't have a conversation ID, fetch the most recent one
    const { data: mostRecentConversation } = useGetConversations({ limit: 1 }, !conversationID);

    const mostRecentConversationID = mostRecentConversation?.length
        ? mostRecentConversation[0]?.conversationID
        : undefined;

    return (
        <Modal size={size ?? ModalSizes.MEDIUM} isVisible={isVisible} exitHandler={onClose}>
            <Frame
                className={classes.links}
                header={<FrameHeader closeFrame={onClose} title={title ?? t("Ask Ai")} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <AiChatInterfaceImpl conversationID={conversationID ?? mostRecentConversationID} />
                    </FrameBody>
                }
            />
        </Modal>
    );
}
