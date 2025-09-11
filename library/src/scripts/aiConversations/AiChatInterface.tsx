/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState, useEffect } from "react";
import debounce from "lodash-es/debounce";
import { useQueryClient } from "@tanstack/react-query";
import classNames from "classnames";
import { uuidv4 } from "@vanilla/utils";
import { MetaItem } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";
import { formatUrl } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { DotLoader } from "@library/loaders/DotLoader";
import {
    useGetConversation,
    useGetConversations,
    usePostReply,
    useStartConversation,
    usePutMessageReaction,
    useAskCommunity,
} from "@library/aiConversations/AiConversations.hooks";
import { ChatInput } from "@library/aiConversations/ChatInputGeneric";
import { IMessage } from "@library/aiConversations/AiConversations.types";
import { aiChatStyles } from "@library/aiConversations/AiChatInterface.styles";
import ChatMessage from "@library/aiConversations/ChatMessage";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Notice from "@library/metas/Notice";
import { ToolTip } from "@library/toolTip/ToolTip";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { DraftRecordType } from "@vanilla/addon-vanilla/drafts/types";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MessageBox } from "@library/messageBox/messageBox";
import { useSizeAnimator } from "@vanilla/react-utils";
import { DisplayState } from "@library/messageBox/messageBox";
import Message from "@library/messages/Message";

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

    // Show a welcome message to the user if they haven't started a conversation yet
    const [showStarterMessage, setShowStarterMessage] = useState(conversationID === undefined);

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
        <div className={classes.wrapper}>
            <div className={classes.outerContainer}>
                {showStarterMessage && (
                    <ChatMessage
                        isAssistant
                        message={{
                            messageID: "starter-message",
                            body: t(
                                "Hi there! I’m your AI Assistant. Ask me anything about this community and I’ll help you find answers—or tell me what you’re looking for, and I’ll help you ask the community.",
                            ),
                        }}
                    />
                )}

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
                            <ChatMessage
                                key={message.messageID}
                                message={message}
                                handleReaction={handleReaction}
                                isAssistant={isAssistant}
                                hasBeenLiked={hasBeenLiked}
                                hasBeenDisliked={hasBeenDisliked}
                                currentModel={conversation?.source}
                            />
                        );
                    })}

                {localMessages.map((msg) => {
                    if (messages?.find((fetchedMessage) => fetchedMessage.body === msg.body)) {
                        return <></>;
                    }
                    return <ChatMessage key={msg.messageID} message={msg} isAssistant={false} />;
                })}

                {conversationID !== undefined && conversationsQuery.status === "loading" && <DotLoader />}

                {conversationsQuery.status === "error" && (
                    <div className={classNames(classes.message, "noBorder")}>
                        <Message type="error" stringContents={t("Error: could not load conversation")} />
                    </div>
                )}

                {isReplyLoading && (
                    <div className={classNames(classes.message, "noBorder")}>
                        <DotLoader />
                    </div>
                )}
            </div>

            <ChatInput onSubmit={handleSubmitMessage} isLoading={!!conversationID && conversationsQuery.isLoading} />
        </div>
    );
}

interface IAiChatModalProps {
    isVisible: boolean;
    onClose: () => void;
    size?: ModalSizes;

    conversationID?: number;
}

// For the search page "Launch Chat" button
export function AiChatInterfaceModal(props: IAiChatModalProps) {
    const { isVisible, onClose, conversationID, size } = props;

    return (
        <Modal size={size ?? ModalSizes.MEDIUM} isVisible={isVisible} exitHandler={onClose}>
            <AiChatInterfaceModalContents conversationID={conversationID} onClose={onClose} />
        </Modal>
    );
}

interface IAiChatInterfaceModalContentsProps {
    onClose: () => void;
    conversationID?: number;
    title?: string;
}

function AiChatInterfaceModalContents(props: IAiChatInterfaceModalContentsProps) {
    const { onClose, conversationID, title } = props;

    const classes = aiChatStyles();

    // if we don't have a conversation ID, fetch the most recent one
    const { data: mostRecentConversation } = useGetConversations({ limit: 1 }, !conversationID);

    const mostRecentConversationID = mostRecentConversation?.length
        ? mostRecentConversation[0]?.conversationID
        : undefined;

    const conversationIDToUse = conversationID ?? mostRecentConversationID;
    const askCommunityMutation = useAskCommunity();
    const askCommunity = askCommunityMutation.mutateAsync;

    const handleAskCommunity = async () => {
        if (conversationIDToUse) {
            const response = await askCommunity({ conversationID: conversationIDToUse });

            if (response) {
                DraftsApi.post({
                    recordType: DraftRecordType.DISCUSSION,
                    attributes: {
                        body: response.body,
                        format: "rich2",
                        draftType: "discussion",
                        draftMeta: {
                            name: response.name,
                            pinLocation: "none",
                            pinned: false,
                            categoryID: response.categoryID,
                            postTypeID: response.postType,
                        },
                        name: response.name,
                    },
                    parentRecordID: response.categoryID,
                    parentRecordType: "category",
                })
                    .then((result) => {
                        window.location.href = formatUrl(`post/editdiscussion/0/${result.draftID}`);
                    })
                    .catch((error) => {
                        console.error("DraftsApi.post error", error);
                    });
            }
        }
    };

    const askCommunityLoadingOrSuccess = askCommunityMutation.isLoading || askCommunityMutation.isSuccess;

    return (
        <Frame
            className={classes.links}
            header={
                <FrameHeader
                    closeFrame={onClose}
                    title={
                        <>
                            {title ?? t("AI Search Assistant")}

                            {
                                <div className={classes.headerSubContainer}>
                                    <ToolTip
                                        label={
                                            <Translate
                                                source="This feature is currently in beta. Please <0>share your feedback</0> with us."
                                                c0={(content) => (
                                                    <SmartLink to="https://success.vanillaforums.com/categories/get-help-support">
                                                        {content}
                                                    </SmartLink>
                                                )}
                                            />
                                        }
                                    >
                                        <span style={{ marginLeft: "10px" }}>
                                            <Notice>{t("BETA")}</Notice>
                                        </span>
                                    </ToolTip>

                                    {mostRecentConversation &&
                                        mostRecentConversation[0]?.source === "WATSONXVNRAGBOT" && (
                                            <MetaItem>
                                                {t("Built with IBM watsonx")}
                                                <Notice className={classes.aiNotice}>{t("AI")}</Notice>
                                            </MetaItem>
                                        )}
                                </div>
                            }
                        </>
                    }
                />
            }
            body={
                <FrameBody hasVerticalPadding className={classes.frameBody}>
                    <div style={askCommunityLoadingOrSuccess ? { opacity: 0.5, pointerEvents: "none" } : {}}>
                        <AiChatInterfaceImpl conversationID={conversationIDToUse} />

                        {askCommunityMutation.isError && (
                            <Message type="error" stringContents={t("Error: could not create community post")} />
                        )}
                    </div>
                </FrameBody>
            }
            footer={
                <FrameFooter>
                    <span className={classes.footerContainer}>
                        <p className={classes.footerMessage}>
                            <Icon icon="info" style={{ verticalAlign: "middle", paddingRight: "5px" }} />
                            <Translate
                                source="AI can make mistakes. You can always ask the community or <0>share your feedback</0>."
                                c0={(content) => (
                                    <SmartLink
                                        to="https://success.vanillaforums.com/categories/get-help-support"
                                        className="footerLink"
                                    >
                                        {content}
                                    </SmartLink>
                                )}
                            />
                        </p>

                        <Button
                            className={classes.askCommunityButton}
                            disabled={conversationIDToUse === undefined || askCommunityLoadingOrSuccess}
                            buttonType={ButtonTypes.PRIMARY}
                            onClick={handleAskCommunity}
                            ariaLabel={t("Ask the Community")}
                        >
                            {askCommunityLoadingOrSuccess ? (
                                <ButtonLoader className={classes.askCommunityButton} />
                            ) : (
                                t("Ask the Community")
                            )}
                        </Button>
                    </span>
                </FrameFooter>
            }
        />
    );
}

interface IAiChatInterfaceMessageBoxProps {
    conversationID?: number;
}

export function AiChatInterfaceMessageBox(props: IAiChatInterfaceMessageBoxProps) {
    const { conversationID } = props;

    const classes = aiChatStyles();

    const [displayState, setDisplayState] = useState<DisplayState>({
        type: "closed",
    });

    const changeDisplayState = (newState: DisplayState) => {
        animator.runWithTransition(() => {
            setDisplayState(newState);
        });
    };

    const animator = useSizeAnimator();

    const messageInboxContents = (
        <AiChatInterfaceModalContents
            conversationID={conversationID}
            onClose={() => setDisplayState({ type: "closed" })}
        />
    );

    return (
        <MessageBox
            displayState={displayState}
            messageInboxContents={messageInboxContents}
            animator={animator}
            dropDownTargetState={"transitioningToMessageInbox"}
            changeDisplayState={changeDisplayState}
            icon={"ai-indicator"}
            dropdownContentsClassName={classes.messageBoxDropdownContents}
        />
    );
}
